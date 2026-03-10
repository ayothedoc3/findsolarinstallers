from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.installer_inquiry import InstallerInquiry
from app.models.listing import Listing
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.installer import InstallerInquiryStatusUpdate

router = APIRouter(prefix="/api/admin/installer-inquiries", tags=["admin-installer-inquiries"])

ALLOWED_STATUSES = {"new", "verified", "closed", "rejected"}


@router.get("")
async def list_installer_inquiries(
    status: str | None = None,
    q: str | None = None,
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    query = select(InstallerInquiry)
    if status:
        query = query.where(InstallerInquiry.status == status)
    if q:
        like = f"%{q}%"
        query = query.where(
            or_(
                InstallerInquiry.name.ilike(like),
                InstallerInquiry.business_name.ilike(like),
                InstallerInquiry.email.ilike(like),
                InstallerInquiry.listing_slug.ilike(like),
            )
        )
    query = query.order_by(InstallerInquiry.created_at.desc())
    result = await db.execute(query)
    inquiries = result.scalars().all()

    items = []
    for inquiry in inquiries:
        listing_name = None
        if inquiry.listing_id:
            listing_result = await db.execute(
                select(Listing.name).where(Listing.id == inquiry.listing_id)
            )
            listing_name = listing_result.scalar_one_or_none()
        items.append({
            "id": inquiry.id,
            "listing_id": inquiry.listing_id,
            "listing_slug": inquiry.listing_slug,
            "listing_name": listing_name,
            "name": inquiry.name,
            "business_name": inquiry.business_name,
            "email": inquiry.email,
            "phone": inquiry.phone,
            "state": inquiry.state,
            "notes": inquiry.notes,
            "status": inquiry.status,
            "source_path": inquiry.source_path,
            "utm_source": inquiry.utm_source,
            "utm_medium": inquiry.utm_medium,
            "utm_campaign": inquiry.utm_campaign,
            "admin_note": inquiry.admin_note,
            "created_at": inquiry.created_at.isoformat() if inquiry.created_at else None,
            "updated_at": inquiry.updated_at.isoformat() if inquiry.updated_at else None,
        })
    return items


@router.put("/{inquiry_id}")
async def update_installer_inquiry(
    inquiry_id: int,
    data: InstallerInquiryStatusUpdate,
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    if data.status not in ALLOWED_STATUSES:
        raise HTTPException(status_code=400, detail="Invalid status")

    result = await db.execute(
        select(InstallerInquiry).where(InstallerInquiry.id == inquiry_id)
    )
    inquiry = result.scalar_one_or_none()
    if not inquiry:
        raise HTTPException(status_code=404, detail="Inquiry not found")

    inquiry.status = data.status
    inquiry.admin_note = data.admin_note
    await db.commit()
    return {"ok": True, "status": inquiry.status}
