"""Imports enriched solar installer data into the PostgreSQL database."""
import hashlib
import json
import logging
from datetime import datetime

from sqlalchemy import create_engine, select, text
from sqlalchemy.orm import Session

from app.config import settings
from app.models.listing import Listing, ListingCategory
from app.models.pipeline import ListingSource, RegionSchedule
from app.models.category import Category

logger = logging.getLogger(__name__)


def get_sync_engine():
    """Create a synchronous SQLAlchemy engine for pipeline use."""
    # Convert asyncpg URL to psycopg2
    url = settings.database_url.replace("+asyncpg", "+psycopg2").replace("postgresql+psycopg2", "postgresql+psycopg2")
    if "postgresql://" in url and "+psycopg2" not in url:
        url = url.replace("postgresql://", "postgresql+psycopg2://")
    return create_engine(url)


def get_sync_session():
    engine = get_sync_engine()
    return Session(engine)


def compute_data_hash(record: dict) -> str:
    """Compute a hash of the record to detect changes."""
    key_fields = ["name", "phone", "website", "rating", "reviews", "full_address"]
    data = {k: str(record.get(k, "")) for k in key_fields}
    return hashlib.md5(json.dumps(data, sort_keys=True).encode()).hexdigest()


def get_category_id_map(session: Session) -> dict[str, int]:
    """Build a map of category slug -> id."""
    result = session.execute(select(Category.id, Category.slug, Category.name))
    cat_map = {}
    for row in result:
        cat_map[row.slug] = row.id
        cat_map[row.name] = row.id  # Also map by name
    return cat_map


# Map service names to category slugs
SERVICE_TO_CATEGORY = {
    "Residential Solar": "residential-solar",
    "Commercial Solar": "commercial-solar",
    "Solar Maintenance": "solar-maintenance",
    "Solar Battery Storage": "solar-battery-storage",
    "Solar Pool Heating": "solar-pool-heating",
    "EV Charger + Solar": "ev-charger-solar",
}


def import_records(enriched_records: list[dict], run_id: int | None = None) -> dict:
    """
    Import enriched records into the database.
    Returns stats dict with new_count, updated_count, skipped_count.
    """
    if not enriched_records:
        return {"new_count": 0, "updated_count": 0, "skipped_count": 0}

    session = get_sync_session()
    stats = {"new_count": 0, "updated_count": 0, "skipped_count": 0}

    try:
        cat_map = get_category_id_map(session)

        for record in enriched_records:
            place_id = record.get("place_id", "").strip()
            if not place_id:
                stats["skipped_count"] += 1
                continue

            data_hash = compute_data_hash(record)

            # Check if listing already exists by google_place_id
            existing = session.execute(
                select(Listing).where(Listing.google_place_id == place_id)
            ).scalar_one_or_none()

            if existing:
                # Check if data changed
                existing_source = session.execute(
                    select(ListingSource).where(ListingSource.google_place_id == place_id)
                ).scalar_one_or_none()

                if existing_source and existing_source.data_hash == data_hash:
                    stats["skipped_count"] += 1
                    continue

                # Update existing listing
                existing.name = record["name"]
                existing.phone = record.get("phone")
                existing.website = record.get("website")
                existing.google_rating = record.get("rating") or None
                existing.total_reviews = record.get("reviews", 0)
                existing.description = record.get("description")
                existing.services_offered = record.get("services_offered", [])
                existing.certifications = record.get("certifications", [])
                existing.panel_brands = record.get("panel_brands", [])

                if existing_source:
                    existing_source.data_hash = data_hash
                    existing_source.last_verified_at = datetime.utcnow()

                stats["updated_count"] += 1
            else:
                # Create new listing
                lat = record.get("latitude", 0)
                lng = record.get("longitude", 0)
                location_wkt = f"SRID=4326;POINT({lng} {lat})" if lat and lng else None

                # Ensure slug is unique in the database
                base_slug = record["slug"]
                slug = base_slug
                counter = 1
                while session.execute(select(Listing).where(Listing.slug == slug)).scalar_one_or_none():
                    slug = f"{base_slug}-{counter}"
                    counter += 1

                listing = Listing(
                    name=record["name"],
                    slug=slug,
                    description=record.get("description"),
                    phone=record.get("phone"),
                    website=record.get("website"),
                    address=record.get("full_address"),
                    city=record.get("city"),
                    state=record.get("state"),
                    zip_code=record.get("zip_code"),
                    location=location_wkt,
                    services_offered=record.get("services_offered", []),
                    panel_brands=record.get("panel_brands", []),
                    certifications=record.get("certifications", []),
                    google_rating=record.get("rating") or None,
                    total_reviews=record.get("reviews", 0),
                    google_place_id=place_id,
                    outscraper_data=record,
                    status="active",
                )
                session.add(listing)
                try:
                    session.flush()  # Get the listing ID
                except Exception as flush_err:
                    logger.warning("Flush failed for %s: %s", record["name"], flush_err)
                    session.rollback()
                    stats["skipped_count"] += 1
                    continue

                # Assign categories
                for service in record.get("services_offered", []):
                    cat_slug = SERVICE_TO_CATEGORY.get(service)
                    if cat_slug and cat_slug in cat_map:
                        lc = ListingCategory(listing_id=listing.id, category_id=cat_map[cat_slug])
                        session.add(lc)

                # Create listing source
                source = ListingSource(
                    google_place_id=place_id,
                    listing_id=listing.id,
                    data_hash=data_hash,
                    last_verified_at=datetime.utcnow(),
                )
                session.add(source)
                stats["new_count"] += 1

        session.commit()
        logger.info("Import complete: %d new, %d updated, %d skipped",
                     stats["new_count"], stats["updated_count"], stats["skipped_count"])
    except Exception as e:
        session.rollback()
        logger.error("Import failed: %s", e)
        raise
    finally:
        session.close()

    return stats
