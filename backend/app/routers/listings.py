from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.listing import Listing, ListingImage
from app.schemas.listing import ListingBrief, ListingResponse, PaginatedResponse

router = APIRouter(prefix="/api/listings", tags=["listings"])


@router.get("", response_model=PaginatedResponse)
async def search_listings(
    q: str | None = None,
    state: str | None = None,
    city: str | None = None,
    services: list[str] = Query(default=[]),
    certifications: list[str] = Query(default=[]),
    min_rating: float | None = None,
    financing: bool | None = None,
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=20, ge=1, le=100),
    sort: str = "rating",
    db: AsyncSession = Depends(get_db),
):
    query = select(Listing).where(Listing.status == "active")

    if q:
        q_like = f"%{q.strip()}%"
        query = query.where(or_(
            Listing.search_vector.op("@@")(func.plainto_tsquery("english", q)),
            Listing.name.ilike(q_like),
            Listing.city.ilike(q_like),
            Listing.state.ilike(q_like),
            Listing.address.ilike(q_like),
            Listing.zip_code.ilike(q_like),
        ))
    if state:
        query = query.where(func.lower(Listing.state) == state.lower())
    if city:
        query = query.where(func.lower(Listing.city) == city.lower())
    if services:
        query = query.where(Listing.services_offered.overlap(services))
    if certifications:
        query = query.where(Listing.certifications.overlap(certifications))
    if min_rating is not None:
        query = query.where(Listing.google_rating >= min_rating)
    if financing is not None:
        query = query.where(Listing.financing_available == financing)

    # Count
    count_query = select(func.count()).select_from(query.subquery())
    total = (await db.execute(count_query)).scalar() or 0

    # Sort
    if sort == "rating":
        query = query.order_by(Listing.google_rating.desc().nulls_last())
    elif sort == "name":
        query = query.order_by(Listing.name)
    elif sort == "newest":
        query = query.order_by(Listing.created_at.desc())
    else:
        query = query.order_by(Listing.google_rating.desc().nulls_last())

    # Paginate
    query = query.offset((page - 1) * per_page).limit(per_page)
    result = await db.execute(query)
    listings = result.scalars().all()

    items = []
    for l in listings:
        img_result = await db.execute(
            select(ListingImage.url).where(ListingImage.listing_id == l.id, ListingImage.is_primary == True).limit(1)
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


@router.get("/{slug}", response_model=ListingResponse)
async def get_listing(slug: str, db: AsyncSession = Depends(get_db)):
    result = await db.execute(
        select(Listing)
        .where(Listing.slug == slug, Listing.status == "active")
        .options(selectinload(Listing.images), selectinload(Listing.categories))
    )
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    return listing
