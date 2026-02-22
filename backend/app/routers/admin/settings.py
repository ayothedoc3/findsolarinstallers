from fastapi import APIRouter, Depends
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
