from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, HTTPException, Query
from pydantic import BaseModel
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.listing import Listing, ListingImage
from app.models.listing_claim import ListingClaim
from app.models.pageview import Pageview
from app.models.plan import ListingPlan
from app.models.user import User
from app.routers.auth import require_role
from app.services.marketplace import is_featured_listing

router = APIRouter(prefix="/api/admin/listings", tags=["admin-listings"])


@router.get("")
async def list_all_listings(
    q: str | None = None,
    status: str | None = None,
    state: str | None = None,
    ownership: str | None = None,
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=50, ge=1, le=200),
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    since = datetime.now(timezone.utc) - timedelta(days=30)
    plan_result = await db.execute(select(ListingPlan))
    plan_lookup = {plan.id: plan for plan in plan_result.scalars().all()}

    query = select(Listing).options(selectinload(Listing.plan))

    if status:
        query = query.where(Listing.status == status)
    if state:
        query = query.where(func.lower(Listing.state) == state.lower())
    if q:
        query = query.where(Listing.name.ilike(f"%{q}%"))
    if ownership == "unowned":
        query = query.where(Listing.owner_id.is_(None))
    elif ownership == "owned":
        query = query.where(Listing.owner_id.isnot(None))

    count_query = select(func.count()).select_from(query.subquery())
    total = (await db.execute(count_query)).scalar() or 0

    query = query.order_by(Listing.created_at.desc())
    query = query.offset((page - 1) * per_page).limit(per_page)
    result = await db.execute(query)
    listings = result.scalars().all()

    # Batch-load owner emails for listings that have owners
    owner_ids = {l.owner_id for l in listings if l.owner_id}
    owner_map: dict[int, str] = {}
    if owner_ids:
        owner_result = await db.execute(
            select(User.id, User.email).where(User.id.in_(owner_ids))
        )
        owner_map = {row.id: row.email for row in owner_result.all()}

    items = []
    for l in listings:
        img_result = await db.execute(
            select(ListingImage.url).where(
                ListingImage.listing_id == l.id, ListingImage.is_primary == True
            ).limit(1)
        )
        primary_img = img_result.scalar_one_or_none()
        quote_requests_30d = (await db.execute(
            select(func.count(ContactRequest.id)).where(
                ContactRequest.listing_id == l.id,
                ContactRequest.created_at >= since,
            )
        )).scalar() or 0
        views_30d = (await db.execute(
            select(func.count(Pageview.id)).where(
                Pageview.path == f"/listing/{l.slug}",
                Pageview.created_at >= since,
            )
        )).scalar() or 0
        item = {
            "id": l.id, "name": l.name, "slug": l.slug, "city": l.city,
            "state": l.state,
            "google_rating": float(l.google_rating) if l.google_rating else None,
            "total_reviews": l.total_reviews,
            "services_offered": l.services_offered or [],
            "certifications": l.certifications or [],
            "financing_available": l.financing_available,
            "primary_image": primary_img,
            "owner_id": l.owner_id,
            "owner_email": owner_map.get(l.owner_id) if l.owner_id else None,
            "plan_id": l.plan_id,
            "plan_name": l.plan.name if l.plan else "Free Profile",
            "is_featured": is_featured_listing(l, plan_lookup),
            "expires_at": l.expires_at.isoformat() if l.expires_at else None,
            "featured_until": l.featured_until.isoformat() if l.featured_until else None,
            "views_30d": int(views_30d),
            "quote_requests_30d": int(quote_requests_30d),
        }
        items.append(item)

    return {
        "items": items, "total": total, "page": page, "per_page": per_page,
        "pages": (total + per_page - 1) // per_page if total > 0 else 0,
    }


@router.put("/{listing_id}/status")
async def update_listing_status(
    listing_id: int,
    status: str = Query(...),
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    if status not in ("active", "pending", "pending_review", "expired", "suspended"):
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


# ─── Owner assignment ─────────────────────────────────────────────────────────


class AssignOwnerRequest(BaseModel):
    owner_id: int | None = None  # None to remove owner


@router.put("/{listing_id}/owner")
async def assign_owner(
    listing_id: int,
    data: AssignOwnerRequest,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Listing).where(Listing.id == listing_id))
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    if data.owner_id is not None:
        owner_result = await db.execute(select(User).where(User.id == data.owner_id))
        owner = owner_result.scalar_one_or_none()
        if not owner:
            raise HTTPException(status_code=404, detail="User not found")
        listing.owner_id = owner.id
        # Auto-promote to business_owner role if they're just a user
        if owner.role == "user":
            owner.role = "business_owner"
    else:
        listing.owner_id = None

    await db.commit()
    return {"ok": True, "owner_id": listing.owner_id}


class UpdateListingPlanRequest(BaseModel):
    plan_id: int
    expires_at: datetime | None = None
    featured_until: datetime | None = None


@router.put("/{listing_id}/plan")
async def update_listing_plan(
    listing_id: int,
    data: UpdateListingPlanRequest,
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    listing_result = await db.execute(select(Listing).where(Listing.id == listing_id))
    listing = listing_result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    plan_result = await db.execute(select(ListingPlan).where(ListingPlan.id == data.plan_id))
    plan = plan_result.scalar_one_or_none()
    if not plan:
        raise HTTPException(status_code=404, detail="Plan not found")

    listing.plan_id = plan.id
    if plan.is_featured:
        ends_at = data.featured_until or data.expires_at or (
            datetime.now(timezone.utc) + timedelta(days=plan.interval_days)
        )
        listing.featured_until = ends_at
        listing.expires_at = data.expires_at or ends_at
        if listing.status in ("pending_review", "pending", "expired"):
            listing.status = "active"
    else:
        listing.featured_until = None
        listing.expires_at = data.expires_at

    await db.commit()
    return {
        "ok": True,
        "plan_id": listing.plan_id,
        "expires_at": listing.expires_at.isoformat() if listing.expires_at else None,
        "featured_until": listing.featured_until.isoformat() if listing.featured_until else None,
    }


@router.post("/bulk-assign-owner")
async def bulk_assign_owner(
    data: dict,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Assign owner to multiple listings at once."""
    listing_ids: list[int] = data.get("listing_ids", [])
    owner_id: int | None = data.get("owner_id")

    if not listing_ids:
        raise HTTPException(status_code=400, detail="No listing IDs provided")

    if owner_id is not None:
        owner_result = await db.execute(select(User).where(User.id == owner_id))
        owner = owner_result.scalar_one_or_none()
        if not owner:
            raise HTTPException(status_code=404, detail="User not found")
        if owner.role == "user":
            owner.role = "business_owner"

    result = await db.execute(select(Listing).where(Listing.id.in_(listing_ids)))
    listings = result.scalars().all()
    for listing in listings:
        listing.owner_id = owner_id

    await db.commit()
    return {"ok": True, "updated": len(listings)}


# ─── Claims management ────────────────────────────────────────────────────────


@router.get("/claims")
async def list_claims(
    status: str | None = None,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    query = select(ListingClaim)
    if status:
        query = query.where(ListingClaim.status == status)
    query = query.order_by(ListingClaim.created_at.desc())
    result = await db.execute(query)
    claims = result.scalars().all()

    # Enrich with listing/user info
    items = []
    for c in claims:
        listing_result = await db.execute(select(Listing.name, Listing.slug, Listing.city, Listing.state).where(Listing.id == c.listing_id))
        listing_info = listing_result.one_or_none()
        user_result = await db.execute(select(User.email, User.company_name).where(User.id == c.user_id))
        user_info = user_result.one_or_none()
        items.append({
            "id": c.id,
            "listing_id": c.listing_id,
            "listing_name": listing_info.name if listing_info else None,
            "listing_slug": listing_info.slug if listing_info else None,
            "listing_city": listing_info.city if listing_info else None,
            "listing_state": listing_info.state if listing_info else None,
            "user_id": c.user_id,
            "user_email": user_info.email if user_info else None,
            "company_name": user_info.company_name if user_info else c.business_name,
            "business_name": c.business_name,
            "verification_note": c.verification_note,
            "admin_note": c.admin_note,
            "status": c.status,
            "created_at": c.created_at.isoformat() if c.created_at else None,
            "resolved_at": c.resolved_at.isoformat() if c.resolved_at else None,
        })
    return items


class ClaimActionRequest(BaseModel):
    action: str  # approve | reject
    admin_note: str | None = None


@router.put("/claims/{claim_id}")
async def resolve_claim(
    claim_id: int,
    data: ClaimActionRequest,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    if data.action not in ("approve", "reject"):
        raise HTTPException(status_code=400, detail="Action must be 'approve' or 'reject'")

    result = await db.execute(select(ListingClaim).where(ListingClaim.id == claim_id))
    claim = result.scalar_one_or_none()
    if not claim:
        raise HTTPException(status_code=404, detail="Claim not found")
    if claim.status != "pending":
        raise HTTPException(status_code=400, detail=f"Claim already {claim.status}")

    claim.admin_note = data.admin_note
    claim.resolved_at = datetime.now(timezone.utc)

    if data.action == "approve":
        claim.status = "approved"
        # Assign the listing to the claiming user
        listing_result = await db.execute(select(Listing).where(Listing.id == claim.listing_id))
        listing = listing_result.scalar_one_or_none()
        if listing:
            listing.owner_id = claim.user_id
        # Auto-promote to business_owner
        claimant_result = await db.execute(select(User).where(User.id == claim.user_id))
        claimant = claimant_result.scalar_one_or_none()
        if claimant and claimant.role == "user":
            claimant.role = "business_owner"
    else:
        claim.status = "rejected"

    await db.commit()
    return {"ok": True, "status": claim.status}
