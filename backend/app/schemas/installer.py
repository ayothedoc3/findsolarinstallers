from datetime import datetime

from pydantic import BaseModel, EmailStr


class InstallerInquiryCreate(BaseModel):
    name: str
    business_name: str
    email: EmailStr
    phone: str | None = None
    state: str
    listing_slug: str | None = None
    notes: str | None = None
    source_path: str
    utm_source: str | None = None
    utm_medium: str | None = None
    utm_campaign: str | None = None


class InstallerInquiryResponse(BaseModel):
    id: int
    listing_id: int | None
    listing_slug: str | None
    name: str
    business_name: str
    email: str
    phone: str | None
    state: str
    notes: str | None
    status: str
    source_path: str
    utm_source: str | None
    utm_medium: str | None
    utm_campaign: str | None
    admin_note: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class InstallerInquiryStatusUpdate(BaseModel):
    status: str
    admin_note: str | None = None
