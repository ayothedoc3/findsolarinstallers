from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select, update
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.api_key import ApiKey
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.admin import ApiKeyCreate, ApiKeyResponse
from app.utils.security import encrypt_api_key

router = APIRouter(prefix="/api/admin/api-keys", tags=["admin-api-keys"])


@router.get("", response_model=list[ApiKeyResponse])
async def list_api_keys(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(ApiKey).order_by(ApiKey.created_at.desc()))
    return result.scalars().all()


@router.post("", response_model=ApiKeyResponse, status_code=status.HTTP_201_CREATED)
async def create_api_key(
    data: ApiKeyCreate,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    # Keep a single active key per service so downstream consumers can resolve
    # the current key unambiguously.
    await db.execute(
        update(ApiKey)
        .where(ApiKey.service == data.service, ApiKey.is_active == True)
        .values(is_active=False)
    )
    api_key = ApiKey(
        name=data.name,
        service=data.service,
        encrypted_key=encrypt_api_key(data.key),
    )
    db.add(api_key)
    await db.commit()
    await db.refresh(api_key)
    return api_key


@router.delete("/{key_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_api_key(
    key_id: int,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(ApiKey).where(ApiKey.id == key_id))
    api_key = result.scalar_one_or_none()
    if not api_key:
        raise HTTPException(status_code=404, detail="API key not found")
    await db.delete(api_key)
    await db.commit()
