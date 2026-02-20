from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.listing import Listing
from app.schemas.contact import ContactCreate, ContactResponse

router = APIRouter(prefix="/api/contact", tags=["contact"])


@router.post("", response_model=ContactResponse, status_code=status.HTTP_201_CREATED)
async def submit_contact(data: ContactCreate, db: AsyncSession = Depends(get_db)):
    result = await db.execute(select(Listing).where(Listing.id == data.listing_id, Listing.status == "active"))
    if not result.scalar_one_or_none():
        raise HTTPException(status_code=404, detail="Listing not found")

    contact = ContactRequest(**data.model_dump())
    db.add(contact)
    await db.commit()
    await db.refresh(contact)
    return contact
