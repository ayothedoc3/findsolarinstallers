from datetime import datetime

from pydantic import BaseModel


class ApiKeyCreate(BaseModel):
    name: str
    service: str
    key: str  # Plain text, will be encrypted before storage


class ApiKeyResponse(BaseModel):
    id: int
    name: str
    service: str
    is_active: bool
    last_used_at: datetime | None
    created_at: datetime

    model_config = {"from_attributes": True}


class SiteSettingUpdate(BaseModel):
    key: str
    value: str


class PipelineRunRequest(BaseModel):
    mode: str  # backfill | weekly | monthly
    regions: list[str] | None = None


class PipelineRunResponse(BaseModel):
    id: int
    mode: str
    status: str
    regions: list[str] | None
    stats: dict | None
    error_message: str | None
    started_at: datetime
    completed_at: datetime | None

    model_config = {"from_attributes": True}


class RegionResponse(BaseModel):
    id: int
    state_code: str
    state_name: str
    priority: int
    enabled: bool
    last_scraped_at: datetime | None
    listing_count: int

    model_config = {"from_attributes": True}


class StatsResponse(BaseModel):
    total_listings: int
    total_states: int
    total_reviews: int
    total_users: int
    total_leads: int
    recent_leads: int  # Last 30 days
