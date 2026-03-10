from fastapi import APIRouter, Depends
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.site_setting import SiteSetting
from app.services.marketplace import get_public_plans, resolve_launch_state

router = APIRouter(prefix="/api/plans", tags=["plans"])


@router.get("/public")
async def get_public_plans_for_site(db: AsyncSession = Depends(get_db)):
    plans = await get_public_plans(db)
    launch = await resolve_launch_state(db)

    contact_email_result = await db.execute(
        select(SiteSetting.value).where(SiteSetting.key == "contact_email")
    )
    contact_email = contact_email_result.scalar_one_or_none() or "info@findsolarinstallers.xyz"

    return {
        "launch_state": launch["display_name"],
        "launch_state_code": launch["state_code"],
        "contact_email": contact_email,
        "plans": [
            {
                "id": plan.id,
                "name": plan.name,
                "price_cents": plan.price_cents,
                "interval_days": plan.interval_days,
                "max_images": plan.max_images,
                "is_featured": plan.is_featured,
                "features": plan.features or [],
            }
            for plan in plans
        ],
    }
