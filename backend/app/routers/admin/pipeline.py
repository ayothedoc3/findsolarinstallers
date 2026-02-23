from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.pipeline import PipelineRun, RegionSchedule
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.admin import PipelineRunRequest, PipelineRunResponse, RegionResponse

router = APIRouter(prefix="/api/admin/pipeline", tags=["admin-pipeline"])


@router.get("/runs", response_model=list[PipelineRunResponse])
async def list_runs(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(PipelineRun).order_by(PipelineRun.started_at.desc()).limit(50))
    return result.scalars().all()


@router.post("/run", response_model=PipelineRunResponse)
async def trigger_run(
    data: PipelineRunRequest,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    run = PipelineRun(mode=data.mode, regions=data.regions, status="queued")
    db.add(run)
    await db.commit()
    await db.refresh(run)

    # Trigger Celery task
    from app.tasks.pipeline_tasks import run_pipeline_task
    run_pipeline_task.delay(data.mode, data.regions, run.id)

    return run


@router.post("/run-inline")
async def trigger_run_inline(
    data: PipelineRunRequest,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Run the pipeline synchronously on the backend (bypasses Celery worker)."""
    import asyncio
    from concurrent.futures import ThreadPoolExecutor
    from app.pipeline.orchestrator import run_pipeline

    run = PipelineRun(mode=data.mode, regions=data.regions, status="queued")
    db.add(run)
    await db.commit()
    await db.refresh(run)

    def _execute():
        run_pipeline(run.id, data.mode, data.regions)

    loop = asyncio.get_event_loop()
    with ThreadPoolExecutor() as pool:
        loop.run_in_executor(pool, _execute)

    return {"id": run.id, "status": "started_inline"}


@router.post("/runs/{run_id}/cancel")
async def cancel_run(
    run_id: int,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Mark a stuck/running pipeline run as failed."""
    result = await db.execute(select(PipelineRun).where(PipelineRun.id == run_id))
    run = result.scalar_one_or_none()
    if not run:
        raise HTTPException(status_code=404, detail="Run not found")
    if run.status not in ("running", "queued"):
        raise HTTPException(status_code=400, detail=f"Run is already {run.status}")

    run.status = "failed"
    run.error_message = "Cancelled by admin"
    run.completed_at = datetime.now(timezone.utc)
    await db.commit()
    return {"ok": True, "status": "failed"}


@router.get("/worker-status")
async def worker_status(
    user: User = Depends(require_role("admin")),
):
    """Check if the Celery worker is alive."""
    try:
        from celery_app import celery
        inspector = celery.control.inspect(timeout=3)
        ping = inspector.ping()
        if ping:
            active = inspector.active() or {}
            return {
                "alive": True,
                "workers": list(ping.keys()),
                "active_tasks": sum(len(v) for v in active.values()),
            }
        return {"alive": False, "workers": [], "active_tasks": 0}
    except Exception as e:
        return {"alive": False, "error": str(e)}


@router.get("/regions", response_model=list[RegionResponse])
async def list_regions(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(RegionSchedule).order_by(RegionSchedule.state_name))
    return result.scalars().all()


@router.get("/debug-scrape")
async def debug_scrape(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Debug endpoint: scrape 1 record, run through cleaner, show raw vs cleaned."""
    import asyncio
    from concurrent.futures import ThreadPoolExecutor

    from app.models.api_key import ApiKey
    from app.utils.security import decrypt_api_key
    from app.pipeline.outscraper_client import SolarOutscraperClient
    from app.pipeline.cleaner import clean_records

    # Get API key
    result = await db.execute(
        select(ApiKey).where(ApiKey.service == "outscraper", ApiKey.is_active == True)
        .order_by(ApiKey.created_at.desc()).limit(1)
    )
    api_key_record = result.scalar_one_or_none()
    if not api_key_record:
        return {"error": "No Outscraper API key found"}

    try:
        decrypted = decrypt_api_key(api_key_record.encrypted_key)
    except Exception as e:
        return {"error": f"Decrypt failed: {e}"}

    client = SolarOutscraperClient(api_key=decrypted, monthly_budget=100)

    # Scrape with limit=3 for a small state to minimize credit usage
    def _scrape():
        return client.scrape_region("Delaware", limit_per_query=3)

    loop = asyncio.get_event_loop()
    with ThreadPoolExecutor() as pool:
        raw = await loop.run_in_executor(pool, _scrape)

    if not raw:
        return {"error": "No raw records returned", "raw_count": 0}

    # Show first 3 raw records
    raw_samples = []
    for r in raw[:3]:
        raw_samples.append({
            "keys": list(r.keys()),
            "name": r.get("name"),
            "full_address": r.get("full_address"),
            "country": r.get("country"),
            "business_status": r.get("business_status"),
            "type": r.get("type"),
            "subtypes": r.get("subtypes"),
            "place_id": r.get("place_id"),
            "city": r.get("city"),
            "state": r.get("state"),
        })

    # Run cleaner
    cleaned = clean_records(raw)

    return {
        "raw_count": len(raw),
        "cleaned_count": len(cleaned),
        "raw_samples": raw_samples,
        "cleaned_samples": cleaned[:3] if cleaned else [],
    }
