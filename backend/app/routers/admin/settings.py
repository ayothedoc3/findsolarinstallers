from fastapi import APIRouter, Depends
from pydantic import BaseModel
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.site_setting import SiteSetting
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.admin import SiteSettingUpdate

router = APIRouter(prefix="/api/admin/settings", tags=["admin-settings"])


@router.get("")
async def list_settings(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(SiteSetting))
    return [{"key": s.key, "value": s.value, "type": s.type} for s in result.scalars().all()]


@router.put("")
async def update_settings(
    updates: list[SiteSettingUpdate],
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    for item in updates:
        result = await db.execute(select(SiteSetting).where(SiteSetting.key == item.key))
        setting = result.scalar_one_or_none()
        if setting:
            setting.value = item.value
        else:
            db.add(SiteSetting(key=item.key, value=item.value))
    await db.commit()
    return {"ok": True}


# ── Stripe Settings ──────────────────────────────────────────────────────────

STRIPE_KEYS = ["stripe_secret_key", "stripe_webhook_secret", "lead_price_cents"]


def _mask_key(value: str | None) -> str:
    if not value:
        return ""
    if len(value) <= 8:
        return "••••••••"
    return value[:7] + "••••" + value[-4:]


@router.get("/stripe")
async def get_stripe_settings(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(
        select(SiteSetting).where(SiteSetting.key.in_(STRIPE_KEYS))
    )
    settings_map = {s.key: s.value for s in result.scalars().all()}

    return {
        "stripe_secret_key": _mask_key(settings_map.get("stripe_secret_key")),
        "stripe_webhook_secret": _mask_key(settings_map.get("stripe_webhook_secret")),
        "lead_price_cents": int(settings_map.get("lead_price_cents", "1999")),
        "has_stripe_key": bool(settings_map.get("stripe_secret_key")),
        "has_webhook_secret": bool(settings_map.get("stripe_webhook_secret")),
    }


class StripeSettingsUpdate(BaseModel):
    stripe_secret_key: str | None = None
    stripe_webhook_secret: str | None = None
    lead_price_cents: int | None = None


@router.put("/stripe")
async def update_stripe_settings(
    data: StripeSettingsUpdate,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    updates: dict[str, str] = {}
    # Only update keys if a real value was sent (not the masked placeholder)
    if data.stripe_secret_key and "••••" not in data.stripe_secret_key:
        updates["stripe_secret_key"] = data.stripe_secret_key
    if data.stripe_webhook_secret and "••••" not in data.stripe_webhook_secret:
        updates["stripe_webhook_secret"] = data.stripe_webhook_secret
    if data.lead_price_cents is not None:
        updates["lead_price_cents"] = str(data.lead_price_cents)

    for key, value in updates.items():
        result = await db.execute(select(SiteSetting).where(SiteSetting.key == key))
        setting = result.scalar_one_or_none()
        if setting:
            setting.value = value
        else:
            db.add(SiteSetting(key=key, value=value, type="string"))
    await db.commit()
    return {"ok": True, "updated": list(updates.keys())}
