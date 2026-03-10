from datetime import datetime, timezone

from sqlalchemy import desc, func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.models.listing import Listing
from app.models.plan import ListingPlan
from app.models.site_setting import SiteSetting
from app.utils.pseo import STATE_NAME_TO_CODE, STATES

DEFAULT_LAUNCH_STATE = "Florida"
FREE_PLAN_ID = 1
FEATURED_PLAN_ID = 2

PUBLIC_PLAN_SPECS = [
    {
        "id": FREE_PLAN_ID,
        "name": "Free Profile",
        "price_cents": 0,
        "interval_days": 365,
        "max_images": 3,
        "is_featured": False,
        "is_active": True,
        "features": [
            "Basic directory profile",
            "Claimable business page",
            "Quote request form",
            "3 photos",
        ],
    },
    {
        "id": FEATURED_PLAN_ID,
        "name": "Verified Featured Profile",
        "price_cents": 9900,
        "interval_days": 30,
        "max_images": 10,
        "is_featured": True,
        "is_active": True,
        "features": [
            "Verified featured badge",
            "Top placement in the launch state",
            "Public phone and website",
            "Monthly performance summary",
            "Up to 10 photos",
        ],
    },
]


def normalize_state_name(value: str | None) -> str:
    if not value:
        return DEFAULT_LAUNCH_STATE
    cleaned = value.strip()
    if not cleaned:
        return DEFAULT_LAUNCH_STATE
    upper = cleaned.upper()
    if upper in STATES:
        return STATES[upper]
    return cleaned


def state_code_for(value: str | None) -> str | None:
    if not value:
        return None
    cleaned = value.strip()
    if not cleaned:
        return None
    upper = cleaned.upper()
    if upper in STATES:
        return upper
    return STATE_NAME_TO_CODE.get(cleaned.lower())


async def resolve_launch_state(db: AsyncSession) -> dict[str, str | int | None]:
    now = datetime.now(timezone.utc)
    result = await db.execute(
        select(
            Listing.state,
            func.count(Listing.id).label("listing_count"),
            func.coalesce(func.sum(Listing.total_reviews), 0).label("review_count"),
        )
        .where(
            Listing.status == "active",
            Listing.state.isnot(None),
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .group_by(Listing.state)
        .order_by(desc("listing_count"), desc("review_count"), Listing.state.asc())
        .limit(1)
    )
    top_state = result.one_or_none()
    raw_state = top_state.state if top_state else None
    listing_count = int(top_state.listing_count) if top_state else 0

    if not raw_state:
        setting_result = await db.execute(
            select(SiteSetting.value).where(SiteSetting.key == "launch_state")
        )
        raw_state = setting_result.scalar_one_or_none()

    display_state = normalize_state_name(raw_state)
    return {
        "state": raw_state or display_state,
        "display_name": display_state,
        "state_code": state_code_for(raw_state or display_state),
        "listing_count": listing_count,
    }


async def get_plan_lookup(db: AsyncSession) -> dict[int, ListingPlan]:
    result = await db.execute(select(ListingPlan))
    return {plan.id: plan for plan in result.scalars().all()}


async def get_public_plans(db: AsyncSession) -> list[ListingPlan]:
    result = await db.execute(
        select(ListingPlan)
        .where(ListingPlan.is_active.is_(True))
        .order_by(ListingPlan.price_cents.asc(), ListingPlan.id.asc())
    )
    return result.scalars().all()


def is_featured_listing(
    listing: Listing,
    plan_lookup: dict[int, ListingPlan] | None = None,
    now: datetime | None = None,
) -> bool:
    now = now or datetime.now(timezone.utc)
    plan = None
    if plan_lookup is not None:
        plan = plan_lookup.get(listing.plan_id or 0)
    if plan is None:
        plan = getattr(listing, "plan", None)
    if not plan or not plan.is_featured:
        return False
    if listing.status != "active":
        return False
    if listing.featured_until is None or listing.featured_until < now:
        return False
    if listing.expires_at is not None and listing.expires_at < now:
        return False
    return True


def is_public_listing_active(listing: Listing, now: datetime | None = None) -> bool:
    now = now or datetime.now(timezone.utc)
    if listing.status != "active":
        return False
    if listing.expires_at is not None and listing.expires_at < now:
        return False
    return True
