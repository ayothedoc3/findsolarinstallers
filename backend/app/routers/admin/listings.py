from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.listing import Listing, ListingImage
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.listing import ListingBrief, PaginatedResponse

router = APIRouter(prefix="/api/admin/listings", tags=["admin-listings"])


@router.get("", response_model=PaginatedResponse)
async def list_all_listings(
    q: str | None = None,
    status: str | None = None,
    state: str | None = None,
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=50, ge=1, le=200),
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    query = select(Listing)

    if status:
        query = query.where(Listing.status == status)
    if state:
        query = query.where(func.lower(Listing.state) == state.lower())
    if q:
        query = query.where(Listing.name.ilike(f"%{q}%"))

    count_query = select(func.count()).select_from(query.subquery())
    total = (await db.execute(count_query)).scalar() or 0

    query = query.order_by(Listing.created_at.desc())
    query = query.offset((page - 1) * per_page).limit(per_page)
    result = await db.execute(query)
    listings = result.scalars().all()

    items = []
    for l in listings:
        img_result = await db.execute(
            select(ListingImage.url).where(
                ListingImage.listing_id == l.id, ListingImage.is_primary == True
            ).limit(1)
        )
        primary_img = img_result.scalar_one_or_none()
        items.append(ListingBrief(
            id=l.id, name=l.name, slug=l.slug, city=l.city, state=l.state,
            google_rating=float(l.google_rating) if l.google_rating else None,
            total_reviews=l.total_reviews, services_offered=l.services_offered or [],
            certifications=l.certifications or [], financing_available=l.financing_available,
            primary_image=primary_img,
        ))

    return PaginatedResponse(
        items=items, total=total, page=page, per_page=per_page,
        pages=(total + per_page - 1) // per_page if total > 0 else 0,
    )


@router.put("/{listing_id}/status")
async def update_listing_status(
    listing_id: int,
    status: str = Query(...),
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    if status not in ("active", "pending", "expired", "suspended"):
        raise HTTPException(status_code=400, detail="Invalid status")
    result = await db.execute(select(Listing).where(Listing.id == listing_id))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")
    listing.status = status
    await db.commit()
    return {"ok": True, "status": status}


@router.delete("/{listing_id}", status_code=204)
async def delete_listing(
    listing_id: int,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Listing).where(Listing.id == listing_id))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")
    await db.delete(listing)
    await db.commit()
