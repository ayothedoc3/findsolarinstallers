import csv
import io
from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Query
from fastapi.concurrency import run_in_threadpool
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.config import settings
from app.database import get_db
from app.models.listing import Listing
from app.models.outreach_log import OutreachLog
from app.models.user import User
from app.routers.auth import require_role
from app.services.email import OUTREACH_SUBJECT, render_outreach_email, send_email
from app.services.marketplace import normalize_state_name, resolve_launch_state, state_code_for

router = APIRouter(prefix="/api/admin/outreach", tags=["admin-outreach"])

SITE_URL = "https://findsolarinstallers.xyz"
MAX_OUTREACH_BATCH = 50


async def _resolve_target_state(db: AsyncSession, state: str | None) -> str:
    if state and state.strip():
        return normalize_state_name(state)
    launch = await resolve_launch_state(db)
    return str(launch["display_name"] or normalize_state_name(None))


def _public_listing_filters(state: str, now: datetime) -> list:
    return [
        func.lower(Listing.state) == state.lower(),
        Listing.status == "active",
        or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
    ]


def _with_email_filters() -> list:
    return [
        Listing.email.isnot(None),
        Listing.email != "",
    ]


async def _sent_listing_ids(db: AsyncSession, state: str, now: datetime) -> set[int]:
    result = await db.execute(
        select(OutreachLog.listing_id)
        .join(Listing, Listing.id == OutreachLog.listing_id)
        .where(
            OutreachLog.status == "sent",
            *_public_listing_filters(state, now),
            *_with_email_filters(),
        )
        .distinct()
    )
    return {row[0] for row in result.all()}


@router.get("/export")
async def export_listings_csv(
    state: str | None = Query(default=None),
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Export public listings for a given state as CSV."""
    now = datetime.now(timezone.utc)
    target_state = await _resolve_target_state(db, state)
    query = (
        select(Listing)
        .where(*_public_listing_filters(target_state, now))
        .order_by(Listing.google_rating.desc().nulls_last(), Listing.total_reviews.desc())
    )
    result = await db.execute(query)
    listings = result.scalars().all()
    sent_ids = await _sent_listing_ids(db, target_state, now)

    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow([
        "id",
        "name",
        "email",
        "phone",
        "website",
        "city",
        "state",
        "slug",
        "listing_url",
        "rating",
        "reviews",
        "outreach_sent",
    ])
    for listing in listings:
        writer.writerow([
            listing.id,
            listing.name,
            listing.email or "",
            listing.phone or "",
            listing.website or "",
            listing.city or "",
            listing.state or "",
            listing.slug,
            f"{SITE_URL}/listing/{listing.slug}",
            float(listing.google_rating) if listing.google_rating else "",
            listing.total_reviews or 0,
            "yes" if listing.id in sent_ids else "",
        ])

    output.seek(0)
    filename = (
        f"installers-{target_state.lower().replace(' ', '-')}-"
        f"{datetime.now(timezone.utc).strftime('%Y%m%d')}.csv"
    )
    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


@router.get("/targets")
async def list_outreach_targets(
    state: str | None = Query(default=None),
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=50, ge=1, le=200),
    filter: str = Query(default="unsent"),
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """List installers eligible for outreach - those with an email on file."""
    if filter not in {"unsent", "sent", "all"}:
        raise HTTPException(status_code=400, detail="Invalid filter")

    now = datetime.now(timezone.utc)
    target_state = await _resolve_target_state(db, state)
    base = select(Listing).where(
        *_public_listing_filters(target_state, now),
        *_with_email_filters(),
    )
    sent_ids = await _sent_listing_ids(db, target_state, now)

    total_all = (
        await db.execute(select(func.count()).select_from(base.subquery()))
    ).scalar() or 0

    filtered = base
    if filter == "unsent" and sent_ids:
        filtered = filtered.where(Listing.id.not_in(sent_ids))
    elif filter == "sent":
        filtered = filtered.where(Listing.id.in_(sent_ids))

    total = (
        await db.execute(select(func.count()).select_from(filtered.subquery()))
    ).scalar() or 0

    result = await db.execute(
        filtered
        .order_by(Listing.google_rating.desc().nulls_last(), Listing.total_reviews.desc())
        .offset((page - 1) * per_page)
        .limit(per_page)
    )
    listings = result.scalars().all()

    items = [
        {
            "id": listing.id,
            "name": listing.name,
            "email": listing.email,
            "phone": listing.phone,
            "website": listing.website,
            "city": listing.city,
            "state": listing.state,
            "slug": listing.slug,
            "listing_url": f"{SITE_URL}/listing/{listing.slug}",
            "google_rating": float(listing.google_rating) if listing.google_rating else None,
            "total_reviews": listing.total_reviews or 0,
            "outreach_sent": listing.id in sent_ids,
        }
        for listing in listings
    ]

    return {
        "items": items,
        "total": total,
        "total_with_email": total_all,
        "total_sent": len(sent_ids),
        "page": page,
        "per_page": per_page,
        "state": target_state,
    }


class SendOutreachRequest(BaseModel):
    listing_ids: list[int]
    featured_example_url: str | None = None


@router.post("/send")
async def send_outreach_emails(
    data: SendOutreachRequest,
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    """Send outreach email to selected listings."""
    if not data.listing_ids:
        raise HTTPException(status_code=400, detail="No listing IDs provided")
    if len(set(data.listing_ids)) > MAX_OUTREACH_BATCH:
        raise HTTPException(
            status_code=400,
            detail=f"Send at most {MAX_OUTREACH_BATCH} outreach emails per batch",
        )
    if not settings.smtp_host:
        raise HTTPException(status_code=400, detail="SMTP is not configured")

    now = datetime.now(timezone.utc)
    requested_ids = set(data.listing_ids)
    result = await db.execute(
        select(Listing).where(
            Listing.id.in_(requested_ids),
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
            *_with_email_filters(),
        )
    )
    listings = result.scalars().all()
    eligible_ids = {listing.id for listing in listings}

    already_sent_result = await db.execute(
        select(OutreachLog.listing_id).where(
            OutreachLog.listing_id.in_(requested_ids),
            OutreachLog.status == "sent",
        )
    )
    already_sent = {row[0] for row in already_sent_result.all()}

    sent = 0
    failed = 0
    skipped = len(requested_ids - eligible_ids)

    launch = await resolve_launch_state(db)
    launch_state = str(launch["display_name"] or normalize_state_name(None))

    for listing in listings:
        if listing.id in already_sent:
            skipped += 1
            continue

        market_state = normalize_state_name(listing.state) if listing.state else launch_state
        market_state_code = state_code_for(listing.state or launch_state)
        listing_url = f"{SITE_URL}/listing/{listing.slug}"
        html, text = render_outreach_email(
            listing.name,
            listing_url,
            market_state,
            market_state_code,
            data.featured_example_url,
        )
        ok = await run_in_threadpool(
            send_email,
            listing.email,
            OUTREACH_SUBJECT,
            html,
            text,
        )

        db.add(
            OutreachLog(
                listing_id=listing.id,
                to_email=listing.email,
                subject=OUTREACH_SUBJECT,
                status="sent" if ok else "failed",
            )
        )

        if ok:
            sent += 1
        else:
            failed += 1

    await db.commit()
    return {"sent": sent, "failed": failed, "skipped": skipped}


@router.get("/log")
async def outreach_log(
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=50, ge=1, le=100),
    _admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    total = (await db.execute(select(func.count(OutreachLog.id)))).scalar() or 0
    result = await db.execute(
        select(OutreachLog)
        .order_by(OutreachLog.created_at.desc())
        .offset((page - 1) * per_page)
        .limit(per_page)
    )
    logs = result.scalars().all()
    return {
        "items": [
            {
                "id": log.id,
                "listing_id": log.listing_id,
                "to_email": log.to_email,
                "subject": log.subject,
                "status": log.status,
                "created_at": log.created_at.isoformat() if log.created_at else None,
            }
            for log in logs
        ],
        "total": total,
        "page": page,
        "per_page": per_page,
    }
