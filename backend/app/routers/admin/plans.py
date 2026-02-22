from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.plan import ListingPlan
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.admin import PlanResponse, PlanUpdate

router = APIRouter(prefix="/api/admin/plans", tags=["admin-plans"])


@router.get("", response_model=list[PlanResponse])
async def list_plans(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(ListingPlan).order_by(ListingPlan.price_cents))
    return result.scalars().all()


@router.put("/{plan_id}", response_model=PlanResponse)
async def update_plan(
    plan_id: int,
    data: PlanUpdate,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(ListingPlan).where(ListingPlan.id == plan_id))
    plan = result.scalar_one_or_none()
    if not plan:
        raise HTTPException(status_code=404, detail="Plan not found")
    for key, val in data.model_dump(exclude_unset=True).items():
        setattr(plan, key, val)
    await db.commit()
    await db.refresh(plan)
    return plan
