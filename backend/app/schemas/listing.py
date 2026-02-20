from datetime import datetime

from pydantic import BaseModel


class ListingBase(BaseModel):
    name: str
    description: str | None = None
    phone: str | None = None
    email: str | None = None
    website: str | None = None
    address: str | None = None
    city: str | None = None
    state: str | None = None
    zip_code: str | None = None
    services_offered: list[str] = []
    panel_brands: list[str] = []
    certifications: list[str] = []
    google_rating: float | None = None
    total_reviews: int = 0
    years_in_business: int | None = None
    installations_completed: int | None = None
    warranty_years: int | None = None
    financing_available: bool = False
    free_consultation: bool = False
    system_size_range: str | None = None
    service_area_radius: str | None = None


class ListingCreate(ListingBase):
    category_ids: list[int] = []
    latitude: float | None = None
    longitude: float | None = None


class ListingUpdate(ListingBase):
    name: str | None = None
    category_ids: list[int] | None = None
    latitude: float | None = None
    longitude: float | None = None


class ListingResponse(ListingBase):
    id: int
    slug: str
    status: str
    owner_id: int | None
    plan_id: int | None
    latitude: float | None = None
    longitude: float | None = None
    images: list["ImageResponse"] = []
    categories: list["CategoryBrief"] = []
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ListingBrief(BaseModel):
    id: int
    name: str
    slug: str
    city: str | None
    state: str | None
    google_rating: float | None
    total_reviews: int
    services_offered: list[str]
    certifications: list[str]
    financing_available: bool
    primary_image: str | None = None

    model_config = {"from_attributes": True}


class ImageResponse(BaseModel):
    id: int
    url: str
    sort_order: int
    is_primary: bool

    model_config = {"from_attributes": True}


class CategoryBrief(BaseModel):
    id: int
    name: str
    slug: str

    model_config = {"from_attributes": True}


class SearchParams(BaseModel):
    q: str | None = None
    state: str | None = None
    city: str | None = None
    services: list[str] | None = None
    certifications: list[str] | None = None
    min_rating: float | None = None
    financing: bool | None = None
    latitude: float | None = None
    longitude: float | None = None
    radius_miles: float | None = None
    page: int = 1
    per_page: int = 20
    sort: str = "rating"  # rating | name | newest | distance


class PaginatedResponse(BaseModel):
    items: list[ListingBrief]
    total: int
    page: int
    per_page: int
    pages: int
