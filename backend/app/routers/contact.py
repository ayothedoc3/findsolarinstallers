import hashlib
from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, HTTPException, Request, status
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.listing import Listing
from app.schemas.contact import ContactCreate, ContactResponse

router = APIRouter(prefix="/api/contact", tags=["contact"])


@router.post("", response_model=ContactResponse, status_code=status.HTTP_201_CREATED)
async def submit_contact(
    data: ContactCreate,
    request: Request,
    db: AsyncSession = Depends(get_db),
):
    if data.hp:
        raise HTTPException(status_code=400, detail="Spam submission rejected")
    if not data.consent:
        raise HTTPException(status_code=400, detail="Consent is required to submit a quote request")

    now = datetime.now(timezone.utc)
    result = await db.execute(
        select(Listing).where(
            Listing.id == data.listing_id,
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
    )
    listing = result.scalar_one_or_none()
    if not listing:
        raise HTTPException(status_code=404, detail="Listing not found")

    ip = request.headers.get("x-forwarded-for", request.client.host if request.client else "0.0.0.0")
    ip = ip.split(",")[0].strip()
    ip_hash = hashlib.sha256(ip.encode()).hexdigest()
    one_hour_ago = datetime.now(timezone.utc) - timedelta(hours=1)
    rate_count = (await db.execute(
        select(func.count(ContactRequest.id)).where(
            ContactRequest.ip_hash == ip_hash,
            ContactRequest.created_at >= one_hour_ago,
        )
    )).scalar() or 0
    if rate_count >= 5:
        raise HTTPException(status_code=429, detail="Too many quote requests from this network. Please try again later.")

    contact = ContactRequest(
        **data.model_dump(exclude={"hp"}),
        ip_hash=ip_hash,
    )
    db.add(contact)
    await db.commit()
    await db.refresh(contact)
    return contact
