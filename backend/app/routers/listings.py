from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Query
from pydantic import BaseModel
from sqlalchemy import and_, case, false, func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.listing import Listing, ListingImage
from app.models.listing_claim import ListingClaim
from app.models.user import User
from app.routers.auth import get_current_user
from app.schemas.listing import ListingBrief, ListingResponse, PaginatedResponse
from app.services.marketplace import get_plan_lookup, is_featured_listing, resolve_launch_state

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
    now = datetime.now(timezone.utc)
    plan_lookup = await get_plan_lookup(db)
    featured_plan_ids = [
        plan_id for plan_id, plan in plan_lookup.items()
        if plan.is_active and plan.is_featured
    ]
    launch = await resolve_launch_state(db)
    launch_state = str(launch["state"] or launch["display_name"])
    featured_plan_condition = Listing.plan_id.in_(featured_plan_ids) if featured_plan_ids else false()
    launch_state_condition = (
        func.lower(Listing.state) == launch_state.lower()
        if launch_state else false()
    )

    query = (
        select(Listing)
        .options(selectinload(Listing.plan))
        .where(
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
    )

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
    featured_rank = case(
        (
            and_(
                featured_plan_condition,
                Listing.featured_until.isnot(None),
                Listing.featured_until >= now,
                launch_state_condition,
            ),
            1,
        ),
        else_=0,
    )
    if sort == "rating":
        query = query.order_by(
            featured_rank.desc(),
            Listing.google_rating.desc().nulls_last(),
            Listing.total_reviews.desc(),
            Listing.created_at.desc(),
        )
    elif sort == "name":
        query = query.order_by(
            featured_rank.desc(),
            Listing.name.asc(),
            Listing.total_reviews.desc(),
            Listing.created_at.desc(),
        )
    elif sort == "newest":
        query = query.order_by(
            featured_rank.desc(),
            Listing.created_at.desc(),
            Listing.total_reviews.desc(),
        )
    else:
        query = query.order_by(
            featured_rank.desc(),
            Listing.google_rating.desc().nulls_last(),
            Listing.total_reviews.desc(),
            Listing.created_at.desc(),
        )

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
            is_featured=is_featured_listing(l, plan_lookup, now),
        ))

    return PaginatedResponse(
        items=items, total=total, page=page, per_page=per_page,
        pages=(total + per_page - 1) // per_page if total > 0 else 0,
    )


@router.get("/{slug}")
async def get_listing(slug: str, db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    plan_lookup = await get_plan_lookup(db)
    result = await db.execute(
        select(Listing)
        .where(
            Listing.slug == slug,
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .options(
            selectinload(Listing.images),
            selectinload(Listing.categories),
            selectinload(Listing.plan),
        )
    )
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    resp = ListingResponse.model_validate(listing)
    data = resp.model_dump()
    is_featured = is_featured_listing(listing, plan_lookup, now)
    plan_name = listing.plan.name if listing.plan else None
    data["is_claimed"] = listing.owner_id is not None
    data["is_featured"] = is_featured
    data["show_direct_contact"] = is_featured
    data["plan_name"] = plan_name
    data["current_plan"] = plan_name
    data["verification_label"] = "Verified Featured Profile" if is_featured else "Basic Profile"
    if not is_featured:
        data["phone"] = None
        data["email"] = None
        data["website"] = None
    return data


class ClaimRequest(BaseModel):
    business_name: str | None = None
    verification_note: str | None = None


@router.post("/{slug}/claim")
async def claim_listing(
    slug: str,
    data: ClaimRequest,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Listing).where(Listing.slug == slug))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    # Check if already owned
    if listing.owner_id is not None:
        raise HTTPException(status_code=400, detail="This listing has already been claimed")

    # Check for existing pending claim by this user
    existing = await db.execute(
        select(ListingClaim).where(
            ListingClaim.listing_id == listing.id,
            ListingClaim.user_id == user.id,
            ListingClaim.status == "pending",
        )
    )
    if existing.scalar_one_or_none():
        raise HTTPException(status_code=400, detail="You already have a pending claim for this listing")

    claim = ListingClaim(
        listing_id=listing.id,
        user_id=user.id,
        business_name=data.business_name,
        verification_note=data.verification_note,
    )
    db.add(claim)
    await db.commit()
    return {"ok": True, "message": "Claim submitted. An admin will review it shortly."}
