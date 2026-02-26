"""Lightweight analytics: tracking beacon + admin dashboard API."""

import hashlib
import logging
import time
from datetime import datetime, timedelta, timezone
from urllib.parse import urlparse

import redis.asyncio as aioredis
from fastapi import APIRouter, Depends, Request
from fastapi.responses import Response
from pydantic import BaseModel
from sqlalchemy import case, distinct, func, select
from sqlalchemy.ext.asyncio import AsyncSession
from user_agents import parse as parse_ua

from app.config import settings
from app.database import get_db
from app.models.pageview import Pageview
from app.models.user import User
from app.routers.auth import require_role

logger = logging.getLogger(__name__)

router = APIRouter(tags=["analytics"])

REDIS_LIVE_KEY = "analytics:live"

# ── Redis connection ──────────────────────────────────────────────────────────

_redis = None


async def get_redis() -> aioredis.Redis:
    global _redis
    if _redis is None:
        _redis = aioredis.from_url(settings.redis_url, decode_responses=True)
    return _redis


# ── Beacon ────────────────────────────────────────────────────────────────────


class BeaconPayload(BaseModel):
    path: str
    referrer: str | None = None
    screen_w: int | None = None
    screen_h: int | None = None
    ua_hash: str | None = None
    duration_ms: int | None = None
    utm_source: str | None = None
    utm_medium: str | None = None
    utm_campaign: str | None = None


SEARCH_ENGINES = {"google", "bing", "yahoo", "duckduckgo", "baidu", "yandex"}
SOCIAL_DOMAINS = {"facebook.com", "twitter.com", "x.com", "linkedin.com", "instagram.com", "youtube.com", "reddit.com", "tiktok.com", "pinterest.com", "t.co"}


def _classify_referrer(domain: str | None) -> str:
    if not domain:
        return "direct"
    d = domain.lower().replace("www.", "")
    for eng in SEARCH_ENGINES:
        if eng in d:
            return "organic"
    if d in SOCIAL_DOMAINS:
        return "social"
    return "referral"


def _extract_domain(url: str | None) -> str | None:
    if not url:
        return None
    try:
        parsed = urlparse(url)
        return parsed.netloc.lower() if parsed.netloc else None
    except Exception:
        return None


def _make_session_id(ip: str, ua_hash: str | None, ua: str | None) -> str:
    """Create a privacy-safe session fingerprint (no raw IP stored)."""
    raw = f"{ip}:{ua_hash or ua or 'unknown'}"
    return hashlib.sha256(raw.encode()).hexdigest()[:32]


def _parse_device(ua_string: str | None) -> tuple[str, str]:
    """Return (device_type, browser) from User-Agent."""
    if not ua_string:
        return ("unknown", "unknown")
    ua = parse_ua(ua_string)
    if ua.is_mobile:
        device = "mobile"
    elif ua.is_tablet:
        device = "tablet"
    elif ua.is_bot:
        device = "bot"
    else:
        device = "desktop"
    browser = ua.browser.family or "unknown"
    return (device, browser)


@router.post("/api/t", status_code=204)
async def track_beacon(
    payload: BeaconPayload,
    request: Request,
    db: AsyncSession = Depends(get_db),
):
    """Lightweight tracking beacon — called on every pageview."""
    ip = request.headers.get("x-forwarded-for", request.client.host if request.client else "0.0.0.0")
    ip = ip.split(",")[0].strip()  # first IP in chain
    ua_string = request.headers.get("user-agent")

    session_id = _make_session_id(ip, payload.ua_hash, ua_string)
    device_type, browser = _parse_device(ua_string)
    referrer_domain = _extract_domain(payload.referrer)

    # If duration_ms is sent, update the previous pageview instead of creating new
    if payload.duration_ms and payload.duration_ms > 0:
        prev = (await db.execute(
            select(Pageview)
            .where(Pageview.session_id == session_id)
            .order_by(Pageview.created_at.desc())
            .limit(1)
        )).scalar_one_or_none()
        if prev and prev.duration_ms is None:
            prev.duration_ms = min(payload.duration_ms, 1800000)  # cap at 30 min
            prev.is_bounce = False
            await db.commit()
            return Response(status_code=204)

    pageview = Pageview(
        session_id=session_id,
        path=payload.path[:500],
        referrer=payload.referrer[:1000] if payload.referrer else None,
        referrer_domain=referrer_domain,
        utm_source=payload.utm_source[:100] if payload.utm_source else None,
        utm_medium=payload.utm_medium[:100] if payload.utm_medium else None,
        utm_campaign=payload.utm_campaign[:100] if payload.utm_campaign else None,
        device_type=device_type,
        browser=browser,
        screen_width=payload.screen_w,
    )
    db.add(pageview)
    await db.commit()

    # Update Redis live set
    try:
        r = await get_redis()
        now = time.time()
        await r.zadd(REDIS_LIVE_KEY, {f"{session_id}:{payload.path}": now})
        # Cleanup entries older than 60s
        await r.zremrangebyscore(REDIS_LIVE_KEY, "-inf", now - 60)
    except Exception as e:
        logger.warning(f"Redis live update failed: {e}")

    return Response(status_code=204)


# ── Admin: Overview ───────────────────────────────────────────────────────────


@router.get("/api/admin/analytics/overview")
async def analytics_overview(
    days: int = 7,
    db: AsyncSession = Depends(get_db),
    _admin: User = Depends(require_role("admin")),
):
    since = datetime.now(timezone.utc) - timedelta(days=days)

    # Core metrics
    stats_q = select(
        func.count(Pageview.id).label("total_pageviews"),
        func.count(distinct(Pageview.session_id)).label("unique_visitors"),
    ).where(Pageview.created_at >= since)
    stats = (await db.execute(stats_q)).one()

    # Bounce rate
    bounce_q = select(
        func.count(Pageview.id).filter(Pageview.is_bounce.is_(True)),
        func.count(Pageview.id),
    ).where(Pageview.created_at >= since)
    bounce_stats = (await db.execute(bounce_q)).one()
    bounce_rate = round(bounce_stats[0] / bounce_stats[1] * 100, 1) if bounce_stats[1] > 0 else 0

    # Avg duration (only non-null)
    avg_dur = (await db.execute(
        select(func.avg(Pageview.duration_ms)).where(
            Pageview.created_at >= since, Pageview.duration_ms.isnot(None)
        )
    )).scalar()

    # Daily chart
    daily_q = (
        select(
            func.date_trunc("day", Pageview.created_at).label("day"),
            func.count(Pageview.id).label("views"),
            func.count(distinct(Pageview.session_id)).label("visitors"),
        )
        .where(Pageview.created_at >= since)
        .group_by("day")
        .order_by("day")
    )
    daily_rows = (await db.execute(daily_q)).all()
    daily = [{"date": r.day.strftime("%Y-%m-%d"), "views": r.views, "visitors": r.visitors} for r in daily_rows]

    # Top pages
    top_pages_q = (
        select(Pageview.path, func.count(Pageview.id).label("views"))
        .where(Pageview.created_at >= since)
        .group_by(Pageview.path)
        .order_by(func.count(Pageview.id).desc())
        .limit(10)
    )
    top_pages = [{"path": r[0], "views": r[1]} for r in (await db.execute(top_pages_q)).all()]

    # Top referrers
    top_refs_q = (
        select(Pageview.referrer_domain, func.count(Pageview.id).label("views"))
        .where(Pageview.created_at >= since, Pageview.referrer_domain.isnot(None))
        .group_by(Pageview.referrer_domain)
        .order_by(func.count(Pageview.id).desc())
        .limit(10)
    )
    top_referrers = [{"domain": r[0], "views": r[1]} for r in (await db.execute(top_refs_q)).all()]

    return {
        "total_pageviews": stats.total_pageviews,
        "unique_visitors": stats.unique_visitors,
        "bounce_rate": bounce_rate,
        "avg_duration_ms": int(avg_dur) if avg_dur else None,
        "daily": daily,
        "top_pages": top_pages,
        "top_referrers": top_referrers,
    }


# ── Admin: Acquisition ───────────────────────────────────────────────────────


@router.get("/api/admin/analytics/acquisition")
async def analytics_acquisition(
    days: int = 30,
    db: AsyncSession = Depends(get_db),
    _admin: User = Depends(require_role("admin")),
):
    since = datetime.now(timezone.utc) - timedelta(days=days)

    # Classify referrers into traffic sources
    source_col = case(
        (Pageview.referrer_domain.is_(None), "direct"),
        *[(Pageview.referrer_domain.contains(eng), "organic") for eng in SEARCH_ENGINES],
        *[(Pageview.referrer_domain.in_(list(SOCIAL_DOMAINS)), "social")],
        else_="referral",
    )

    sources_q = (
        select(source_col.label("source"), func.count(Pageview.id).label("views"))
        .where(Pageview.created_at >= since)
        .group_by("source")
    )
    sources = {r[0]: r[1] for r in (await db.execute(sources_q)).all()}

    # UTM campaigns
    utm_q = (
        select(
            Pageview.utm_source, Pageview.utm_medium, Pageview.utm_campaign,
            func.count(Pageview.id).label("views"),
        )
        .where(Pageview.created_at >= since, Pageview.utm_source.isnot(None))
        .group_by(Pageview.utm_source, Pageview.utm_medium, Pageview.utm_campaign)
        .order_by(func.count(Pageview.id).desc())
        .limit(20)
    )
    campaigns = [
        {"source": r[0], "medium": r[1], "campaign": r[2], "views": r[3]}
        for r in (await db.execute(utm_q)).all()
    ]

    # Top referrer domains (non-null, non-search)
    referrers_q = (
        select(Pageview.referrer_domain, func.count(Pageview.id).label("views"))
        .where(
            Pageview.created_at >= since,
            Pageview.referrer_domain.isnot(None),
        )
        .group_by(Pageview.referrer_domain)
        .order_by(func.count(Pageview.id).desc())
        .limit(20)
    )
    referrers = [{"domain": r[0], "views": r[1]} for r in (await db.execute(referrers_q)).all()]

    return {
        "sources": sources,
        "campaigns": campaigns,
        "referrers": referrers,
    }


# ── Admin: Pages ──────────────────────────────────────────────────────────────


@router.get("/api/admin/analytics/pages")
async def analytics_pages(
    days: int = 30,
    db: AsyncSession = Depends(get_db),
    _admin: User = Depends(require_role("admin")),
):
    since = datetime.now(timezone.utc) - timedelta(days=days)

    pages_q = (
        select(
            Pageview.path,
            func.count(Pageview.id).label("views"),
            func.count(distinct(Pageview.session_id)).label("unique_visitors"),
            func.avg(Pageview.duration_ms).label("avg_duration"),
            func.sum(case((Pageview.is_bounce.is_(True), 1), else_=0)).label("bounces"),
        )
        .where(Pageview.created_at >= since)
        .group_by(Pageview.path)
        .order_by(func.count(Pageview.id).desc())
        .limit(50)
    )
    rows = (await db.execute(pages_q)).all()

    pages = []
    for r in rows:
        pages.append({
            "path": r.path,
            "views": r.views,
            "unique_visitors": r.unique_visitors,
            "avg_duration_ms": int(r.avg_duration) if r.avg_duration else None,
            "bounce_rate": round(r.bounces / r.views * 100, 1) if r.views > 0 else 0,
        })

    return {"pages": pages}


# ── Admin: Live ───────────────────────────────────────────────────────────────


@router.get("/api/admin/analytics/live")
async def analytics_live(
    _admin: User = Depends(require_role("admin")),
):
    try:
        r = await get_redis()
        now = time.time()
        # Get visitors active in last 30 seconds
        members = await r.zrangebyscore(REDIS_LIVE_KEY, now - 30, now, withscores=True)
        visitors = []
        seen_sessions = set()
        for member, score in members:
            parts = member.split(":", 1)
            if len(parts) == 2:
                sid, path = parts
                if sid not in seen_sessions:
                    seen_sessions.add(sid)
                    visitors.append({
                        "session_id": sid[:8] + "...",
                        "path": path,
                        "seconds_ago": int(now - score),
                    })
        return {"active_visitors": len(seen_sessions), "visitors": visitors}
    except Exception as e:
        logger.warning(f"Redis live query failed: {e}")
        return {"active_visitors": 0, "visitors": [], "error": str(e)}
