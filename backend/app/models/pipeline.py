from datetime import datetime

from sqlalchemy import Boolean, DateTime, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import ARRAY, JSONB
from sqlalchemy.orm import Mapped, mapped_column

from app.database import Base


class PipelineRun(Base):
    __tablename__ = "pipeline_runs"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    mode: Mapped[str] = mapped_column(String(20), nullable=False)
    status: Mapped[str] = mapped_column(String(20), default="running")
    regions = mapped_column(ARRAY(String))
    stats = mapped_column(JSONB, default=dict)
    error_message: Mapped[str | None] = mapped_column(Text)
    started_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())
    completed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))


class RegionSchedule(Base):
    __tablename__ = "region_schedule"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    state_code: Mapped[str] = mapped_column(String(2), unique=True, nullable=False)
    state_name: Mapped[str] = mapped_column(String(50), nullable=False)
    priority: Mapped[int] = mapped_column(Integer, default=5)
    enabled: Mapped[bool] = mapped_column(Boolean, default=True)
    last_scraped_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    listing_count: Mapped[int] = mapped_column(Integer, default=0)


class ListingSource(Base):
    __tablename__ = "listing_sources"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    google_place_id: Mapped[str] = mapped_column(String(255), unique=True, nullable=False)
    listing_id: Mapped[int | None] = mapped_column(Integer)
    data_hash: Mapped[str | None] = mapped_column(String(64))
    first_seen_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())
    last_verified_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
