from datetime import datetime

from sqlalchemy import Boolean, DateTime, Index, Integer, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database import Base


class Pageview(Base):
    __tablename__ = "pageviews"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    session_id: Mapped[str] = mapped_column(String(64), nullable=False)
    path: Mapped[str] = mapped_column(String(500), nullable=False)
    referrer: Mapped[str | None] = mapped_column(String(1000))
    referrer_domain: Mapped[str | None] = mapped_column(String(255))
    utm_source: Mapped[str | None] = mapped_column(String(100))
    utm_medium: Mapped[str | None] = mapped_column(String(100))
    utm_campaign: Mapped[str | None] = mapped_column(String(100))
    device_type: Mapped[str | None] = mapped_column(String(10))
    browser: Mapped[str | None] = mapped_column(String(50))
    screen_width: Mapped[int | None] = mapped_column(Integer)
    duration_ms: Mapped[int | None] = mapped_column(Integer)
    is_bounce: Mapped[bool] = mapped_column(Boolean, default=True)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    __table_args__ = (
        Index("ix_pageviews_created_at", "created_at"),
        Index("ix_pageviews_path_created", "path", "created_at"),
        Index("ix_pageviews_session_created", "session_id", "created_at"),
    )
