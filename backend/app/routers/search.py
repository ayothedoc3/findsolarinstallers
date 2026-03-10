from datetime import datetime, timezone

from fastapi import APIRouter, Depends
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.listing import Listing
from app.models.plan import ListingPlan
from app.services.marketplace import is_featured_listing, resolve_launch_state

router = APIRouter(prefix="/api", tags=["search"])


@router.get("/stats")
async def get_stats(db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    public_filter = (
        Listing.status == "active",
        or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
    )
    active = select(Listing).where(*public_filter).subquery()

    total = (await db.execute(select(func.count()).select_from(active))).scalar() or 0
    states = (await db.execute(
        select(func.count(func.distinct(Listing.state))).where(*public_filter)
    )).scalar() or 0
    reviews = (await db.execute(
        select(func.sum(Listing.total_reviews)).where(*public_filter)
    )).scalar() or 0
    launch = await resolve_launch_state(db)

    plans_result = await db.execute(select(ListingPlan))
    plan_lookup = {plan.id: plan for plan in plans_result.scalars().all()}
    featured_result = await db.execute(
        select(Listing).where(
            *public_filter,
            Listing.state == launch["state"],
        )
    )
    active_featured = sum(1 for listing in featured_result.scalars().all() if is_featured_listing(listing, plan_lookup, now))

    return {
        "total_listings": total,
        "total_states": states,
        "total_reviews": reviews or 0,
        "launch_state": launch["display_name"],
        "launch_state_code": launch["state_code"],
        "launch_state_listing_count": launch["listing_count"],
        "active_featured_count": active_featured,
    }


@router.get("/states")
async def list_states(db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    result = await db.execute(
        select(Listing.state, func.count(Listing.id).label("count"))
        .where(
            Listing.status == "active",
            Listing.state.isnot(None),
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .group_by(Listing.state)
        .order_by(Listing.state)
    )
    return [{"state": row.state, "count": row.count} for row in result.all()]
