from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.auth import UserResponse

router = APIRouter(prefix="/api/admin/users", tags=["admin-users"])


@router.get("")
async def list_users(
    q: str | None = None,
    role: str | None = None,
    page: int = Query(default=1, ge=1),
    per_page: int = Query(default=50, ge=1, le=200),
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    query = select(User)
    if role:
        query = query.where(User.role == role)
    if q:
        query = query.where(
            User.email.ilike(f"%{q}%") | User.first_name.ilike(f"%{q}%") | User.last_name.ilike(f"%{q}%")
        )

    count_query = select(func.count()).select_from(query.subquery())
    total = (await db.execute(count_query)).scalar() or 0

    query = query.order_by(User.created_at.desc())
    query = query.offset((page - 1) * per_page).limit(per_page)
    result = await db.execute(query)
    users = result.scalars().all()

    return {
        "items": [UserResponse.model_validate(u) for u in users],
        "total": total,
        "page": page,
        "per_page": per_page,
    }


@router.put("/{user_id}/role")
async def update_user_role(
    user_id: int,
    role: str = Query(...),
    admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    if role not in ("user", "business_owner", "admin"):
        raise HTTPException(status_code=400, detail="Invalid role")
    result = await db.execute(select(User).where(User.id == user_id))
    target = result.scalar_one_or_none()
    if not target:
        raise HTTPException(status_code=404, detail="User not found")
    target.role = role
    await db.commit()
    return {"ok": True, "role": role}


@router.put("/{user_id}/toggle-active")
async def toggle_user_active(
    user_id: int,
    admin: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(User).where(User.id == user_id))
    target = result.scalar_one_or_none()
    if not target:
        raise HTTPException(status_code=404, detail="User not found")
    target.is_active = not target.is_active
    await db.commit()
    return {"ok": True, "is_active": target.is_active}
