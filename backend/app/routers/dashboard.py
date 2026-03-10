import re
from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.lead_purchase import LeadPurchase
from app.models.listing import Listing, ListingCategory, ListingImage
from app.models.category import Category
from app.models.pageview import Pageview
from app.models.plan import ListingPlan
from app.models.user import User
from app.routers.auth import get_current_user
from app.schemas.contact import ContactResponse
from app.schemas.listing import ListingCreate, ListingResponse, ListingUpdate
from app.services.marketplace import FREE_PLAN_ID, is_featured_listing

router = APIRouter(prefix="/api/dashboard", tags=["dashboard"])


def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"[^\w\s-]", "", text)
    text = re.sub(r"[\s_]+", "-", text)
    return re.sub(r"-+", "-", text).strip("-")


async def _get_plan_lookup(db: AsyncSession) -> dict[int, ListingPlan]:
    result = await db.execute(select(ListingPlan))
    return {plan.id: plan for plan in result.scalars().all()}


async def _listing_metrics(db: AsyncSession, listing: Listing) -> tuple[int, int]:
    since = datetime.now(timezone.utc) - timedelta(days=30)
    views = (await db.execute(
        select(func.count(Pageview.id)).where(
            Pageview.path == f"/listing/{listing.slug}",
            Pageview.created_at >= since,
        )
    )).scalar() or 0
    quote_requests = (await db.execute(
        select(func.count(ContactRequest.id)).where(
            ContactRequest.listing_id == listing.id,
            ContactRequest.created_at >= since,
        )
    )).scalar() or 0
    return int(views), int(quote_requests)


@router.get("/listings")
async def my_listings(
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    plan_lookup = await _get_plan_lookup(db)
    now = datetime.now(timezone.utc)
    result = await db.execute(
        select(Listing)
        .where(Listing.owner_id == user.id)
        .options(selectinload(Listing.images), selectinload(Listing.categories), selectinload(Listing.plan))
        .order_by(Listing.created_at.desc())
    )
    listings = result.scalars().all()
    items = []
    for listing in listings:
        payload = ListingResponse.model_validate(listing).model_dump()
        views_30d, quote_requests_30d = await _listing_metrics(db, listing)
        plan_name = listing.plan.name if listing.plan else "Free Profile"
        featured = is_featured_listing(listing, plan_lookup, now)
        payload.update({
            "plan_name": plan_name,
            "current_plan": plan_name,
            "is_featured": featured,
            "show_direct_contact": featured,
            "verification_label": "Verified Featured Profile" if featured else "Basic Profile",
            "views_30d": views_30d,
            "quote_requests_30d": quote_requests_30d,
            "expires_at": listing.expires_at.isoformat() if listing.expires_at else None,
        })
        items.append(payload)
    return items


@router.post("/listings", response_model=ListingResponse, status_code=201)
async def create_listing(
    data: ListingCreate,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    slug = slugify(data.name)
    # Ensure unique slug
    existing = await db.execute(select(Listing).where(Listing.slug == slug))
    if existing.scalar_one_or_none():
        slug = f"{slug}-{int(datetime.now(timezone.utc).timestamp())}"

    listing = Listing(
        owner_id=user.id,
        plan_id=FREE_PLAN_ID,
        name=data.name,
        slug=slug,
        description=data.description,
        phone=data.phone,
        email=data.email,
        website=data.website,
        address=data.address,
        city=data.city,
        state=data.state,
        zip_code=data.zip_code,
        services_offered=data.services_offered,
        panel_brands=data.panel_brands,
        certifications=data.certifications,
        google_rating=data.google_rating,
        total_reviews=data.total_reviews,
        years_in_business=data.years_in_business,
        installations_completed=data.installations_completed,
        warranty_years=data.warranty_years,
        financing_available=data.financing_available,
        free_consultation=data.free_consultation,
        system_size_range=data.system_size_range,
        service_area_radius=data.service_area_radius,
        status="pending_review",
    )

    if data.latitude and data.longitude:
        listing.location = f"SRID=4326;POINT({data.longitude} {data.latitude})"

    db.add(listing)
    await db.flush()

    # Add categories
    for cat_id in data.category_ids:
        db.add(ListingCategory(listing_id=listing.id, category_id=cat_id))

    await db.commit()

    # Reload with relationships
    result = await db.execute(
        select(Listing)
        .where(Listing.id == listing.id)
        .options(selectinload(Listing.images), selectinload(Listing.categories), selectinload(Listing.plan))
    )
    created_listing = result.scalar_one()
    payload = ListingResponse.model_validate(created_listing).model_dump()
    payload.update({
        "plan_name": created_listing.plan.name if created_listing.plan else "Free Profile",
        "current_plan": created_listing.plan.name if created_listing.plan else "Free Profile",
        "is_featured": False,
        "show_direct_contact": False,
        "verification_label": "Basic Profile",
        "views_30d": 0,
        "quote_requests_30d": 0,
        "expires_at": created_listing.expires_at.isoformat() if created_listing.expires_at else None,
    })
    return payload


@router.put("/listings/{listing_id}", response_model=ListingResponse)
async def update_listing(
    listing_id: int,
    data: ListingUpdate,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Listing).where(Listing.id == listing_id, Listing.owner_id == user.id))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    update_data = data.model_dump(exclude_unset=True, exclude={"category_ids", "latitude", "longitude"})
    for key, val in update_data.items():
        setattr(listing, key, val)

    if data.latitude is not None and data.longitude is not None:
        listing.location = f"SRID=4326;POINT({data.longitude} {data.latitude})"

    if data.category_ids is not None:
        await db.execute(
            select(ListingCategory).where(ListingCategory.listing_id == listing.id)
        )
        # Delete existing
        from sqlalchemy import delete
        await db.execute(delete(ListingCategory).where(ListingCategory.listing_id == listing.id))
        for cat_id in data.category_ids:
            db.add(ListingCategory(listing_id=listing.id, category_id=cat_id))

    await db.commit()

    result = await db.execute(
        select(Listing)
        .where(Listing.id == listing.id)
        .options(selectinload(Listing.images), selectinload(Listing.categories))
    )
    return result.scalar_one()


@router.delete("/listings/{listing_id}", status_code=204)
async def delete_my_listing(
    listing_id: int,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Listing).where(Listing.id == listing_id, Listing.owner_id == user.id))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")
    await db.delete(listing)
    await db.commit()


@router.get("/leads")
async def my_leads(
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    plan_lookup = await _get_plan_lookup(db)
    now = datetime.now(timezone.utc)
    # Get leads for all listings owned by user
    owned_listings_result = await db.execute(
        select(Listing).options(selectinload(Listing.plan)).where(Listing.owner_id == user.id)
    )
    owned_listings = owned_listings_result.scalars().all()
    listing_ids = [listing.id for listing in owned_listings]

    if not listing_ids:
        return []

    listing_map = {listing.id: listing for listing in owned_listings}
    featured_listing_ids = {
        listing.id for listing in owned_listings
        if is_featured_listing(listing, plan_lookup, now)
    }

    result = await db.execute(
        select(ContactRequest)
        .where(ContactRequest.listing_id.in_(listing_ids))
        .order_by(ContactRequest.created_at.desc())
    )
    leads = result.scalars().all()

    # Get all completed purchases for this user
    purchased_result = await db.execute(
        select(LeadPurchase.contact_request_id).where(
            LeadPurchase.user_id == user.id,
            LeadPurchase.status == "completed",
        )
    )
    unlocked_ids = {r[0] for r in purchased_result.all()}

    responses = []
    for lead in leads:
        listing = listing_map.get(lead.listing_id)
        is_unlocked = lead.id in unlocked_ids or lead.listing_id in featured_listing_ids
        resp = ContactResponse(
            id=lead.id,
            listing_id=lead.listing_id,
            listing_name=listing.name if listing else None,
            name=lead.name,
            email=lead.email if is_unlocked else None,
            phone=lead.phone if is_unlocked else None,
            message=lead.message if is_unlocked else None,
            project_type=lead.project_type,
            zip_code=lead.zip_code,
            is_read=lead.is_read,
            is_unlocked=is_unlocked,
            requires_featured_upgrade=not is_unlocked,
            created_at=lead.created_at,
        )
        responses.append(resp)
    return responses


@router.put("/leads/{lead_id}/read")
async def mark_lead_read(
    lead_id: int,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    # Verify lead belongs to user's listing
    result = await db.execute(
        select(ContactRequest).where(ContactRequest.id == lead_id)
    )
    lead = result.scalar_one_or_none()
    if not lead:
        raise HTTPException(status_code=404, detail="Lead not found")

    listing = await db.execute(select(Listing).where(Listing.id == lead.listing_id, Listing.owner_id == user.id))
    if not listing.scalar_one_or_none():
        raise HTTPException(status_code=403, detail="Not your listing")

    lead.is_read = True
    await db.commit()
    return {"ok": True}


@router.get("/profile")
async def get_profile(user: User = Depends(get_current_user)):
    return {
        "id": user.id,
        "email": user.email,
        "first_name": user.first_name,
        "last_name": user.last_name,
        "company_name": user.company_name,
        "phone": user.phone,
        "role": user.role,
    }


@router.put("/profile")
async def update_profile(
    data: dict,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    allowed = {"first_name", "last_name", "company_name", "phone"}
    for key, val in data.items():
        if key in allowed:
            setattr(user, key, val)
    await db.commit()
    return {
        "id": user.id,
        "email": user.email,
        "first_name": user.first_name,
        "last_name": user.last_name,
        "company_name": user.company_name,
        "phone": user.phone,
        "role": user.role,
    }


@router.get("/stats")
async def dashboard_stats(
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    active_listings = (await db.execute(
        select(func.count(Listing.id)).where(
            Listing.owner_id == user.id,
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= datetime.now(timezone.utc)),
        )
    )).scalar() or 0

    listing_ids_result = await db.execute(select(Listing.id).where(Listing.owner_id == user.id))
    listing_ids = [r[0] for r in listing_ids_result.all()]

    total_leads = 0
    unread_leads = 0
    if listing_ids:
        total_leads = (await db.execute(
            select(func.count(ContactRequest.id)).where(ContactRequest.listing_id.in_(listing_ids))
        )).scalar() or 0
        unread_leads = (await db.execute(
            select(func.count(ContactRequest.id)).where(
                ContactRequest.listing_id.in_(listing_ids),
                ContactRequest.is_read == False,
            )
        )).scalar() or 0

    return {
        "active_listings": active_listings,
        "total_leads": total_leads,
        "unread_leads": unread_leads,
    }
