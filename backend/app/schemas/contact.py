from datetime import datetime

from pydantic import BaseModel, EmailStr


class ContactCreate(BaseModel):
    listing_id: int
    name: str
    email: EmailStr
    phone: str | None = None
    message: str | None = None
    project_type: str | None = None
    zip_code: str | None = None


class ContactResponse(BaseModel):
    id: int
    listing_id: int
    name: str
    email: str | None
    phone: str | None
    message: str | None
    project_type: str | None
    zip_code: str | None
    is_read: bool
    is_unlocked: bool = False
    created_at: datetime

    model_config = {"from_attributes": True}
