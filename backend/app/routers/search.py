from fastapi import APIRouter, Depends
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.listing import Listing

router = APIRouter(prefix="/api", tags=["search"])


@router.get("/stats")
async def get_stats(db: AsyncSession = Depends(get_db)):
    active = select(Listing).where(Listing.status == "active").subquery()

    total = (await db.execute(select(func.count()).select_from(active))).scalar() or 0
    states = (await db.execute(
        select(func.count(func.distinct(Listing.state))).where(Listing.status == "active")
    )).scalar() or 0
    reviews = (await db.execute(
        select(func.sum(Listing.total_reviews)).where(Listing.status == "active")
    )).scalar() or 0

    return {"total_listings": total, "total_states": states, "total_reviews": reviews or 0}


@router.get("/states")
async def list_states(db: AsyncSession = Depends(get_db)):
    result = await db.execute(
        select(Listing.state, func.count(Listing.id).label("count"))
        .where(Listing.status == "active", Listing.state.isnot(None))
        .group_by(Listing.state)
        .order_by(Listing.state)
    )
    return [{"state": row.state, "count": row.count} for row in result.all()]
