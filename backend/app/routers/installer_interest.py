from fastapi import APIRouter, Depends
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.installer_inquiry import InstallerInquiry
from app.models.listing import Listing
from app.schemas.installer import InstallerInquiryCreate

router = APIRouter(prefix="/api/installer-interest", tags=["installer-interest"])


@router.post("")
async def create_installer_interest(
    data: InstallerInquiryCreate,
    db: AsyncSession = Depends(get_db),
):
    listing_id = None
    if data.listing_slug:
        listing_result = await db.execute(
            select(Listing.id).where(Listing.slug == data.listing_slug)
        )
        listing_id = listing_result.scalar_one_or_none()

    inquiry = InstallerInquiry(
        listing_id=listing_id,
        listing_slug=data.listing_slug,
        name=data.name,
        business_name=data.business_name,
        email=data.email,
        phone=data.phone,
        state=data.state,
        notes=data.notes,
        source_path=data.source_path,
        utm_source=data.utm_source,
        utm_medium=data.utm_medium,
        utm_campaign=data.utm_campaign,
    )
    db.add(inquiry)
    await db.commit()
    await db.refresh(inquiry)
    return {"ok": True, "inquiry_id": inquiry.id}
