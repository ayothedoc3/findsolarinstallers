"""Pipeline orchestrator — coordinates scraping, cleaning, enrichment, and import."""
import logging
from datetime import datetime

from sqlalchemy import create_engine, select, update
from sqlalchemy.orm import Session

from app.config import settings
from app.models.pipeline import PipelineRun, RegionSchedule, ListingSource
from app.models.api_key import ApiKey
from app.pipeline.outscraper_client import (
    OutscraperAuthError,
    OutscraperCreditsExhaustedError,
    SolarOutscraperClient,
)
from app.pipeline.cleaner import clean_records
from app.pipeline.enricher import enrich_records
from app.pipeline.importer import import_records
from app.utils.security import decrypt_api_key

logger = logging.getLogger(__name__)

US_STATES = {
    "AL": "Alabama", "AK": "Alaska", "AZ": "Arizona", "AR": "Arkansas",
    "CA": "California", "CO": "Colorado", "CT": "Connecticut", "DE": "Delaware",
    "FL": "Florida", "GA": "Georgia", "HI": "Hawaii", "ID": "Idaho",
    "IL": "Illinois", "IN": "Indiana", "IA": "Iowa", "KS": "Kansas",
    "KY": "Kentucky", "LA": "Louisiana", "ME": "Maine", "MD": "Maryland",
    "MA": "Massachusetts", "MI": "Michigan", "MN": "Minnesota", "MS": "Mississippi",
    "MO": "Missouri", "MT": "Montana", "NE": "Nebraska", "NV": "Nevada",
    "NH": "New Hampshire", "NJ": "New Jersey", "NM": "New Mexico", "NY": "New York",
    "NC": "North Carolina", "ND": "North Dakota", "OH": "Ohio", "OK": "Oklahoma",
    "OR": "Oregon", "PA": "Pennsylvania", "RI": "Rhode Island", "SC": "South Carolina",
    "SD": "South Dakota", "TN": "Tennessee", "TX": "Texas", "UT": "Utah",
    "VT": "Vermont", "VA": "Virginia", "WA": "Washington", "WV": "West Virginia",
    "WI": "Wisconsin", "WY": "Wyoming", "DC": "District of Columbia",
}


def _get_sync_session():
    url = settings.database_url.replace("+asyncpg", "+psycopg2")
    engine = create_engine(url)
    return Session(engine)


def _get_outscraper_key(session: Session) -> str | None:
    """Retrieve the decrypted Outscraper API key from the database."""
    result = session.execute(
        select(ApiKey)
        .where(ApiKey.service == "outscraper", ApiKey.is_active == True)
        .order_by(ApiKey.created_at.desc(), ApiKey.id.desc())
        .limit(1)
    )
    api_key_record = result.scalar_one_or_none()
    if not api_key_record:
        return None

    try:
        decrypted = decrypt_api_key(api_key_record.encrypted_key)
        # Update last_used_at
        api_key_record.last_used_at = datetime.utcnow()
        session.commit()
        return decrypted
    except Exception as e:
        logger.error("Failed to decrypt Outscraper API key: %s", e)
        return None


def _get_regions_for_mode(session: Session, mode: str, regions: list[str] | None) -> list[dict]:
    """Determine which regions to process based on mode."""
    if regions:
        return [{"state_code": r.upper(), "state_name": US_STATES.get(r.upper(), r)} for r in regions]

    if mode == "backfill":
        # Resume-friendly behavior:
        # 1) finish states that have never been scraped before
        # 2) once all states were scraped at least once, re-verify oldest first
        never_scraped = session.execute(
            select(RegionSchedule)
            .where(RegionSchedule.enabled == True, RegionSchedule.last_scraped_at.is_(None))
            .order_by(RegionSchedule.priority.desc(), RegionSchedule.state_name)
        ).scalars().all()
        if never_scraped:
            return [{"state_code": r.state_code, "state_name": r.state_name} for r in never_scraped]

        result = session.execute(
            select(RegionSchedule)
            .where(RegionSchedule.enabled == True)
            .order_by(RegionSchedule.last_scraped_at.asc().nulls_first(), RegionSchedule.priority.desc())
        )
        return [{"state_code": r.state_code, "state_name": r.state_name} for r in result.scalars().all()]

    elif mode == "weekly":
        result = session.execute(
            select(RegionSchedule)
            .where(RegionSchedule.enabled == True)
            .order_by(RegionSchedule.last_scraped_at.asc().nulls_first(), RegionSchedule.priority.desc())
            .limit(5)
        )
        return [{"state_code": r.state_code, "state_name": r.state_name} for r in result.scalars().all()]

    elif mode == "monthly":
        result = session.execute(
            select(RegionSchedule).where(RegionSchedule.enabled == True).order_by(RegionSchedule.state_name)
        )
        return [{"state_code": r.state_code, "state_name": r.state_name} for r in result.scalars().all()]

    return []


def run_pipeline(run_id: int, mode: str, regions: list[str] | None = None):
    """
    Execute the pipeline for a given run.
    This is the main entry point called by Celery tasks.
    """
    session = _get_sync_session()

    try:
        # Update run status to running
        run = session.get(PipelineRun, run_id)
        if not run:
            logger.error("Pipeline run %d not found", run_id)
            return
        run.status = "running"
        run.started_at = datetime.utcnow()
        session.commit()

        # Get Outscraper API key
        api_key = _get_outscraper_key(session)
        if not api_key:
            run.status = "failed"
            run.error_message = "No active Outscraper API key found. Add one in Admin > API Keys."
            run.completed_at = datetime.utcnow()
            session.commit()
            return

        client = SolarOutscraperClient(api_key=api_key, monthly_budget=int(settings.monthly_credit_budget))

        # Get regions to process
        region_list = _get_regions_for_mode(session, mode, regions)
        if not region_list:
            run.status = "completed"
            run.stats = {"message": "No regions to process"}
            run.completed_at = datetime.utcnow()
            session.commit()
            return

        region_order = [r["state_code"] for r in region_list]

        total_stats = {
            "regions_processed": 0,
            "total_raw": 0,
            "total_cleaned": 0,
            "total_new": 0,
            "total_updated": 0,
            "total_skipped": 0,
            "credits_used": 0,
            "errors": [],
            "failed_regions": [],
            "completed_regions": [],
            "total_regions": len(region_list),
        }

        def _persist_progress(i: int | None = None, state_name: str | None = None):
            completed = set(total_stats.get("completed_regions", []))
            remaining_regions = [code for code in region_order if code not in completed]

            progress_value = (
                f"{i + 1}/{len(region_list)}"
                if i is not None else
                f"{total_stats['regions_processed']}/{len(region_list)}"
            )
            run.stats = {
                **total_stats,
                "current_region": state_name,
                "progress": progress_value,
                "regions_remaining": len(remaining_regions),
                "remaining_regions": remaining_regions,
            }
            session.commit()

        _persist_progress()

        for i, region in enumerate(region_list):
            state_code = region["state_code"]
            state_name = region["state_name"]
            logger.info("Processing %s (%s) [%d/%d]...", state_name, state_code, i + 1, len(region_list))

            try:
                # Step 1: Scrape
                raw_records = client.scrape_region(state_name)
                total_stats["total_raw"] += len(raw_records)

                if not raw_records:
                    logger.warning("No records for %s, skipping", state_name)
                    region_row = session.execute(
                        select(RegionSchedule).where(RegionSchedule.state_code == state_code)
                    ).scalar_one_or_none()
                    if region_row:
                        region_row.last_scraped_at = datetime.utcnow()
                    total_stats["regions_processed"] += 1
                    total_stats["completed_regions"].append(state_code)
                    _persist_progress(i, state_name)
                    continue

                # Step 2: Clean
                cleaned = clean_records(raw_records)
                total_stats["total_cleaned"] += len(cleaned)

                # Step 3: Enrich
                enriched = enrich_records(cleaned)

                # Step 4: Import
                import_stats = import_records(enriched, run_id)
                total_stats["total_new"] += import_stats["new_count"]
                total_stats["total_updated"] += import_stats["updated_count"]
                total_stats["total_skipped"] += import_stats["skipped_count"]

                # Update region schedule
                region_row = session.execute(
                    select(RegionSchedule).where(RegionSchedule.state_code == state_code)
                ).scalar_one_or_none()
                if region_row:
                    region_row.last_scraped_at = datetime.utcnow()
                    region_row.listing_count = (region_row.listing_count or 0) + import_stats["new_count"]

                total_stats["regions_processed"] += 1
                total_stats["completed_regions"].append(state_code)

                # Save progress after each region so admin UI shows live stats
                _persist_progress(i, state_name)

                logger.info("Region %s done: %d new, %d updated", state_name, import_stats["new_count"], import_stats["updated_count"])

            except OutscraperCreditsExhaustedError as e:
                logger.error("Outscraper credits exhausted for %s: %s", state_name, e)
                total_stats["errors"].append(f"{state_name}: {str(e)}")
                total_stats["failed_regions"].append(state_code)
                total_stats["credits_used"] = client.get_credits_used()
                _persist_progress(i, state_name)
                run.status = "paused_no_credits"
                run.error_message = (
                    "Outscraper credits appear exhausted. Add credits and start another Backfill run; "
                    "it will resume with remaining never-scraped states first."
                )
                run.completed_at = datetime.utcnow()
                session.commit()
                return
            except OutscraperAuthError as e:
                logger.error("Outscraper auth failed for %s: %s", state_name, e)
                total_stats["errors"].append(f"{state_name}: {str(e)}")
                total_stats["failed_regions"].append(state_code)
                total_stats["credits_used"] = client.get_credits_used()
                _persist_progress(i, state_name)
                run.status = "failed"
                run.error_message = "Outscraper API key was rejected. Update it in Admin > API Keys."
                run.completed_at = datetime.utcnow()
                session.commit()
                return
            except Exception as e:
                logger.error("Error processing %s: %s", state_name, e, exc_info=True)
                total_stats["errors"].append(f"{state_name}: {str(e)}")
                total_stats["failed_regions"].append(state_code)
                # Save progress even on error
                _persist_progress(i, state_name)

        # Update run with final stats
        total_stats["credits_used"] = client.get_credits_used()
        run.status = "completed" if not total_stats["errors"] else "completed_with_errors"
        remaining_regions = [code for code in region_order if code not in set(total_stats.get("completed_regions", []))]
        run.stats = {
            **total_stats,
            "current_region": None,
            "progress": f"{total_stats['regions_processed']}/{len(region_list)}",
            "regions_remaining": len(remaining_regions),
            "remaining_regions": remaining_regions,
        }
        run.completed_at = datetime.utcnow()
        session.commit()

        logger.info("Pipeline run %d complete: %s", run_id, total_stats)

    except Exception as e:
        logger.error("Pipeline run %d failed: %s", run_id, e)
        try:
            run = session.get(PipelineRun, run_id)
            if run:
                run.status = "failed"
                run.error_message = str(e)
                run.completed_at = datetime.utcnow()
                session.commit()
        except Exception:
            pass
    finally:
        session.close()
