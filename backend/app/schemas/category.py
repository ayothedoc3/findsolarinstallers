from pydantic import BaseModel


class CategoryResponse(BaseModel):
    id: int
    parent_id: int | None
    name: str
    slug: str
    description: str | None
    icon: str | None
    sort_order: int
    listing_count: int = 0

    model_config = {"from_attributes": True}


class CategoryCreate(BaseModel):
    name: str
    slug: str
    parent_id: int | None = None
    description: str | None = None
    icon: str | None = None
    sort_order: int = 0
