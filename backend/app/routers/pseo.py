"""Programmatic SEO: server-rendered location+service landing pages."""

import json
import logging
from datetime import datetime, timezone
from pathlib import Path

from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates
from sqlalchemy import and_, case, func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.database import get_db
from app.models.generated_page import GeneratedPage
from app.models.listing import Listing
from app.utils.pseo import (
    SERVICE_SLUGS, SERVICE_TO_SLUG, STATES, STATE_NAME_TO_CODE,
    generate_faqs, generate_h1, generate_meta_description, generate_title,
    make_pseo_slug, parse_pseo_slug,
)
from app.routers.auth import require_role
from app.services.marketplace import FEATURED_PLAN_ID, is_featured_listing, resolve_launch_state

logger = logging.getLogger(__name__)

router = APIRouter(tags=["pseo"])

TEMPLATE_DIR = Path(__file__).resolve().parent.parent / "templates"
templates = Jinja2Templates(directory=str(TEMPLATE_DIR))

BASE_URL = "https://findsolarinstallers.xyz"
MIN_INSTALLERS = 1  # noindex if fewer


# ── Public: serve pSEO page ────────────────────────────────────────────────

@router.get("/{slug}", response_class=HTMLResponse)
async def serve_pseo_page(slug: str, request: Request, db: AsyncSession = Depends(get_db)):
    parsed = parse_pseo_slug(slug)
    if not parsed:
        raise HTTPException(404)

    # Look up or auto-create the page
    page = (await db.execute(
        select(GeneratedPage).where(GeneratedPage.slug == slug)
    )).scalar_one_or_none()

    # Query matching installers
    installers, count, avg_rating, total_reviews = await _query_installers(db, parsed)

    if not page:
        # Auto-create on first visit
        page = GeneratedPage(
            slug=slug,
            page_type=parsed["page_type"],
            title=generate_title(parsed),
            h1=generate_h1(parsed),
            meta_description=generate_meta_description(parsed, count, avg_rating),
            city=parsed.get("city"),
            state=parsed.get("state"),
            state_code=parsed.get("state_code"),
            zip_code=parsed.get("zip_code"),
            service=parsed.get("service"),
            filter_json=json.loads(json.dumps({k: v for k, v in parsed.items() if v is not None})),
            installer_count=count,
            avg_rating=float(avg_rating) if avg_rating else None,
            total_reviews=total_reviews,
            hit_count=1,
        )
        db.add(page)
        await db.commit()
        await db.refresh(page)
    else:
        page.title = generate_title(parsed)
        page.h1 = generate_h1(parsed)
        page.meta_description = generate_meta_description(parsed, count, avg_rating)
        page.installer_count = count
        page.avg_rating = float(avg_rating) if avg_rating else None
        page.total_reviews = total_reviews
        page.hit_count += 1
        await db.commit()
        await db.refresh(page)

    noindex = count < MIN_INSTALLERS
    faqs = generate_faqs(parsed, count, avg_rating)

    # Build related pages links
    related = _build_related_links(parsed)

    return templates.TemplateResponse("pseo/location_page.html", {
        "request": request,
        "page": page,
        "installers": installers,
        "count": count,
        "avg_rating": round(float(avg_rating), 1) if avg_rating else None,
        "total_reviews": total_reviews,
        "faqs": faqs,
        "related": related,
        "noindex": noindex,
        "base_url": BASE_URL,
        "parsed": parsed,
    })


# ── Admin: pre-generate pages ──────────────────────────────────────────────

@router.post("/api/admin/pseo/generate")
async def generate_pages(
    db: AsyncSession = Depends(get_db),
    _admin=Depends(require_role("admin")),
):
    """Pre-generate pSEO pages for all city+state combos and services."""
    now = datetime.now(timezone.utc)
    # Get distinct city/state combinations
    result = await db.execute(
        select(Listing.city, Listing.state)
        .where(
            Listing.status == "active",
            Listing.city.isnot(None),
            Listing.state.isnot(None),
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .group_by(Listing.city, Listing.state)
    )
    locations = result.all()

    # Get distinct states
    state_result = await db.execute(
        select(Listing.state)
        .where(
            Listing.status == "active",
            Listing.state.isnot(None),
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .group_by(Listing.state)
    )
    states = [r[0] for r in state_result.all()]

    created = 0
    services = list(SERVICE_SLUGS.values()) + [None]  # None = all services

    # State-level pages
    for state in states:
        state_code = STATE_NAME_TO_CODE.get(state.lower(), "")
        for service in services:
            slug = make_pseo_slug(service=service, state=state)
            if not slug:
                continue
            existing = (await db.execute(
                select(GeneratedPage.id).where(GeneratedPage.slug == slug)
            )).scalar_one_or_none()
            if existing:
                continue

            parsed = parse_pseo_slug(slug)
            if not parsed:
                continue
            _, count, avg_r, total_r = await _query_installers(db, parsed)

            page = GeneratedPage(
                slug=slug,
                page_type=parsed["page_type"],
                title=generate_title(parsed),
                h1=generate_h1(parsed),
                meta_description=generate_meta_description(parsed, count, avg_r),
                city=None, state=state, state_code=state_code,
                zip_code=None, service=service,
                filter_json=json.loads(json.dumps({k: v for k, v in parsed.items() if v is not None})),
                installer_count=count,
                avg_rating=float(avg_r) if avg_r else None,
                total_reviews=total_r,
            )
            db.add(page)
            created += 1

    # City-level pages
    for city, state in locations:
        state_code = STATE_NAME_TO_CODE.get(state.lower(), "")
        for service in services:
            slug = make_pseo_slug(service=service, city=city, state=state)
            if not slug:
                continue
            existing = (await db.execute(
                select(GeneratedPage.id).where(GeneratedPage.slug == slug)
            )).scalar_one_or_none()
            if existing:
                continue

            parsed = parse_pseo_slug(slug)
            if not parsed:
                continue
            _, count, avg_r, total_r = await _query_installers(db, parsed)

            page = GeneratedPage(
                slug=slug,
                page_type=parsed["page_type"],
                title=generate_title(parsed),
                h1=generate_h1(parsed),
                meta_description=generate_meta_description(parsed, count, avg_r),
                city=city, state=state, state_code=state_code,
                zip_code=None, service=service,
                filter_json=json.loads(json.dumps({k: v for k, v in parsed.items() if v is not None})),
                installer_count=count,
                avg_rating=float(avg_r) if avg_r else None,
                total_reviews=total_r,
            )
            db.add(page)
            created += 1

    await db.commit()
    total = (await db.execute(select(func.count(GeneratedPage.id)))).scalar()
    return {"created": created, "total_pages": total}


@router.get("/api/admin/pseo/stats")
async def pseo_stats(
    db: AsyncSession = Depends(get_db),
    _admin=Depends(require_role("admin")),
):
    total = (await db.execute(select(func.count(GeneratedPage.id)))).scalar()
    total_hits = (await db.execute(select(func.sum(GeneratedPage.hit_count)))).scalar() or 0
    top_pages = (await db.execute(
        select(GeneratedPage.slug, GeneratedPage.hit_count, GeneratedPage.installer_count)
        .order_by(GeneratedPage.hit_count.desc())
        .limit(20)
    )).all()
    return {
        "total_pages": total,
        "total_hits": total_hits,
        "top_pages": [{"slug": r[0], "hits": r[1], "installers": r[2]} for r in top_pages],
    }


# ── Helpers ─────────────────────────────────────────────────────────────────

async def _query_installers(db: AsyncSession, parsed: dict) -> tuple[list, int, float | None, int]:
    """Query matching installers and return (list, count, avg_rating, total_reviews)."""
    now = datetime.now(timezone.utc)
    launch = await resolve_launch_state(db)
    launch_state = str(launch["state"] or launch["display_name"] or "")
    conditions = [
        Listing.status == "active",
        or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
    ]

    state = parsed.get("state")
    city = parsed.get("city")
    zip_code = parsed.get("zip_code")
    service = parsed.get("service")

    if state:
        conditions.append(func.lower(Listing.state) == state.lower())
    if city:
        conditions.append(func.lower(Listing.city) == city.lower())
    if zip_code:
        conditions.append(Listing.zip_code == zip_code)
    if service:
        conditions.append(Listing.services_offered.any(service))

    featured_rank = case(
        (
            and_(
                Listing.plan_id == FEATURED_PLAN_ID,
                Listing.featured_until.isnot(None),
                Listing.featured_until >= now,
                func.lower(Listing.state) == launch_state.lower(),
            ),
            1,
        ),
        else_=0,
    )
    base_q = select(Listing).options(selectinload(Listing.plan)).where(and_(*conditions))

    # Get stats
    stats_q = select(
        func.count(Listing.id),
        func.avg(Listing.google_rating),
        func.sum(Listing.total_reviews),
    ).where(and_(*conditions))
    stats = (await db.execute(stats_q)).one()
    count = stats[0] or 0
    avg_rating = float(stats[1]) if stats[1] else None
    total_reviews = stats[2] or 0

    # Get installer list (top 20 by rating)
    result = await db.execute(
        base_q.order_by(
            featured_rank.desc(),
            Listing.google_rating.desc().nulls_last(),
            Listing.total_reviews.desc(),
            Listing.created_at.desc(),
        ).limit(20)
    )
    installers = result.scalars().all()
    for installer in installers:
        installer.is_featured_public = is_featured_listing(installer, now=now)
        installer.show_direct_contact = installer.is_featured_public

    return installers, count, avg_rating, total_reviews


def _build_related_links(parsed: dict) -> list[dict[str, str]]:
    """Build related page links for internal linking."""
    links = []
    state = parsed.get("state")
    state_code = parsed.get("state_code")
    city = parsed.get("city")
    service = parsed.get("service")

    # If on a city page, link to the state page
    if city and state:
        slug = make_pseo_slug(service=service, state=state)
        if slug:
            svc = f"{service} " if service else ""
            links.append({"url": f"/{slug}", "text": f"{svc}Solar Installers in {state}"})

    # If on a service page, link to the same location without service
    if service:
        slug = make_pseo_slug(city=city, state=state, state_code=state_code)
        if slug:
            loc = f"{city}, {state_code}" if city else state
            links.append({"url": f"/{slug}", "text": f"All Solar Installers in {loc}"})

    # Link to other services for the same location
    for svc_name in ["Residential", "Commercial", "Battery Storage", "EV Charger"]:
        if svc_name == service:
            continue
        slug = make_pseo_slug(service=svc_name, city=city, state=state, state_code=state_code)
        if slug:
            loc = f"{city}, {state_code}" if city else (state or "")
            links.append({"url": f"/{slug}", "text": f"{svc_name} Solar in {loc}"})
        if len(links) >= 8:
            break

    return links
