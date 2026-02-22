import re
from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.listing import Listing, ListingCategory, ListingImage
from app.models.category import Category
from app.models.user import User
from app.routers.auth import get_current_user
from app.schemas.contact import ContactResponse
from app.schemas.listing import ListingCreate, ListingResponse, ListingUpdate

router = APIRouter(prefix="/api/dashboard", tags=["dashboard"])


def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"[^\w\s-]", "", text)
    text = re.sub(r"[\s_]+", "-", text)
    return re.sub(r"-+", "-", text).strip("-")


@router.get("/listings")
async def my_listings(
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(
        select(Listing)
        .where(Listing.owner_id == user.id)
        .options(selectinload(Listing.images), selectinload(Listing.categories))
        .order_by(Listing.created_at.desc())
    )
    listings = result.scalars().all()
    return [ListingResponse.model_validate(l) for l in listings]


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
        status="active",
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
        .options(selectinload(Listing.images), selectinload(Listing.categories))
    )
    return result.scalar_one()


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
    # Get leads for all listings owned by user
    listing_ids_result = await db.execute(
        select(Listing.id).where(Listing.owner_id == user.id)
    )
    listing_ids = [r[0] for r in listing_ids_result.all()]

    if not listing_ids:
        return []

    result = await db.execute(
        select(ContactRequest)
        .where(ContactRequest.listing_id.in_(listing_ids))
        .order_by(ContactRequest.created_at.desc())
    )
    return [ContactResponse.model_validate(c) for c in result.scalars().all()]


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
        select(func.count(Listing.id)).where(Listing.owner_id == user.id, Listing.status == "active")
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
