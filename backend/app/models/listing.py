from datetime import datetime

from geoalchemy2 import Geography
from sqlalchemy import (
    Boolean, DateTime, ForeignKey, Index, Integer, Numeric, String, Text, func,
)
from sqlalchemy.dialects.postgresql import ARRAY, JSONB, TSVECTOR
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.database import Base


class Listing(Base):
    __tablename__ = "listings"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    owner_id: Mapped[int | None] = mapped_column(Integer, ForeignKey("users.id"))
    plan_id: Mapped[int | None] = mapped_column(Integer, ForeignKey("listing_plans.id"))
    status: Mapped[str] = mapped_column(String(20), default="active", index=True)

    # Core business info
    name: Mapped[str] = mapped_column(String(255), nullable=False)
    slug: Mapped[str] = mapped_column(String(255), unique=True, nullable=False)
    description: Mapped[str | None] = mapped_column(Text)
    phone: Mapped[str | None] = mapped_column(String(20))
    email: Mapped[str | None] = mapped_column(String(255))
    website: Mapped[str | None] = mapped_column(String(500))

    # Address
    address: Mapped[str | None] = mapped_column(String(500))
    city: Mapped[str | None] = mapped_column(String(100))
    state: Mapped[str | None] = mapped_column(String(50), index=True)
    zip_code: Mapped[str | None] = mapped_column(String(10))
    location = mapped_column(Geography(geometry_type="POINT", srid=4326), nullable=True)

    # Solar-specific arrays
    services_offered = mapped_column(ARRAY(String), default=list)
    panel_brands = mapped_column(ARRAY(String), default=list)
    certifications = mapped_column(ARRAY(String), default=list)

    # Numeric
    google_rating: Mapped[float | None] = mapped_column(Numeric(2, 1))
    total_reviews: Mapped[int] = mapped_column(Integer, default=0)
    years_in_business: Mapped[int | None] = mapped_column(Integer)
    installations_completed: Mapped[int | None] = mapped_column(Integer)
    warranty_years: Mapped[int | None] = mapped_column(Integer)

    # Options
    financing_available: Mapped[bool] = mapped_column(Boolean, default=False)
    free_consultation: Mapped[bool] = mapped_column(Boolean, default=False)
    system_size_range: Mapped[str | None] = mapped_column(String(20))
    service_area_radius: Mapped[str | None] = mapped_column(String(20))

    # Full-text search
    search_vector = mapped_column(TSVECTOR)

    # Pipeline tracking
    google_place_id: Mapped[str | None] = mapped_column(String(255), unique=True)
    outscraper_data = mapped_column(JSONB)

    # Timestamps
    featured_until: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    expires_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())
    updated_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    # Relationships
    owner = relationship("User", back_populates="listings")
    plan = relationship("ListingPlan")
    images = relationship("ListingImage", back_populates="listing", cascade="all, delete-orphan")
    categories = relationship("Category", secondary="listing_categories")

    __table_args__ = (
        Index("idx_listings_search", "search_vector", postgresql_using="gin"),
        Index("idx_listings_services", "services_offered", postgresql_using="gin"),
        Index("idx_listings_brands", "panel_brands", postgresql_using="gin"),
        Index("idx_listings_certs", "certifications", postgresql_using="gin"),
        Index("idx_listings_rating", google_rating.desc().nulls_last()),
    )


class ListingCategory(Base):
    __tablename__ = "listing_categories"

    listing_id: Mapped[int] = mapped_column(Integer, ForeignKey("listings.id", ondelete="CASCADE"), primary_key=True)
    category_id: Mapped[int] = mapped_column(Integer, ForeignKey("categories.id", ondelete="CASCADE"), primary_key=True)


class ListingImage(Base):
    __tablename__ = "listing_images"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    listing_id: Mapped[int] = mapped_column(Integer, ForeignKey("listings.id", ondelete="CASCADE"))
    url: Mapped[str] = mapped_column(String(500), nullable=False)
    sort_order: Mapped[int] = mapped_column(Integer, default=0)
    is_primary: Mapped[bool] = mapped_column(Boolean, default=False)
    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())

    listing = relationship("Listing", back_populates="images")
