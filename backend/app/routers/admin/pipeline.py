from fastapi import APIRouter, Depends
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


@router.get("/regions", response_model=list[RegionResponse])
async def list_regions(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(RegionSchedule).order_by(RegionSchedule.state_name))
    return result.scalars().all()
