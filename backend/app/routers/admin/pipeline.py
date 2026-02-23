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
