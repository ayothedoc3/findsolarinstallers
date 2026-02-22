from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.listing import Listing
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.admin import StatsResponse

router = APIRouter(prefix="/api/admin/stats", tags=["admin-stats"])


@router.get("", response_model=StatsResponse)
async def get_admin_stats(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    total_listings = (await db.execute(select(func.count(Listing.id)))).scalar() or 0
    total_states = (await db.execute(
        select(func.count(func.distinct(Listing.state))).where(Listing.state.isnot(None))
    )).scalar() or 0
    total_reviews = (await db.execute(select(func.coalesce(func.sum(Listing.total_reviews), 0)))).scalar() or 0
    total_users = (await db.execute(select(func.count(User.id)))).scalar() or 0
    total_leads = (await db.execute(select(func.count(ContactRequest.id)))).scalar() or 0

    thirty_days_ago = datetime.now(timezone.utc) - timedelta(days=30)
    recent_leads = (await db.execute(
        select(func.count(ContactRequest.id)).where(ContactRequest.created_at >= thirty_days_ago)
    )).scalar() or 0

    return StatsResponse(
        total_listings=total_listings,
        total_states=total_states,
        total_reviews=total_reviews,
        total_users=total_users,
        total_leads=total_leads,
        recent_leads=recent_leads,
    )
