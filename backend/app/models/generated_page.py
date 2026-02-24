from datetime import datetime

from sqlalchemy import DateTime, Integer, Numeric, String, Text, func
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.orm import Mapped, mapped_column

from app.database import Base


class GeneratedPage(Base):
    __tablename__ = "generated_pages"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    slug: Mapped[str] = mapped_column(String(500), unique=True, nullable=False, index=True)
    page_type: Mapped[str] = mapped_column(String(30), nullable=False)  # state, city, service_state, service_city, zip
    title: Mapped[str] = mapped_column(String(300), nullable=False)
    meta_description: Mapped[str] = mapped_column(Text)
    h1: Mapped[str] = mapped_column(String(300))

    # Parsed filter components
    city: Mapped[str | None] = mapped_column(String(100))
    state: Mapped[str | None] = mapped_column(String(50))
    state_code: Mapped[str | None] = mapped_column(String(2))
    zip_code: Mapped[str | None] = mapped_column(String(10))
    service: Mapped[str | None] = mapped_column(String(100))
    filter_json = mapped_column(JSONB)

    # Stats (cached, updated on generation)
    installer_count: Mapped[int] = mapped_column(Integer, default=0)
    avg_rating: Mapped[float | None] = mapped_column(Numeric(3, 2))
    total_reviews: Mapped[int] = mapped_column(Integer, default=0)

    # Tracking
    hit_count: Mapped[int] = mapped_column(Integer, default=0)
    is_active: Mapped[bool] = mapped_column(default=True)
    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())
    updated_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
