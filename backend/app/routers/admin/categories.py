from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.category import Category
from app.models.listing import ListingCategory
from app.models.user import User
from app.routers.auth import require_role
from app.schemas.category import CategoryCreate, CategoryResponse

router = APIRouter(prefix="/api/admin/categories", tags=["admin-categories"])


@router.get("", response_model=list[CategoryResponse])
async def list_categories(
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    listing_count = (
        select(func.count(ListingCategory.listing_id))
        .where(ListingCategory.category_id == Category.id)
        .correlate(Category)
        .scalar_subquery()
    )
    result = await db.execute(
        select(Category, listing_count.label("listing_count"))
        .order_by(Category.sort_order)
    )
    cats = []
    for row in result.all():
        cat = row[0]
        cat.listing_count = row[1] or 0
        cats.append(cat)
    return cats


@router.post("", response_model=CategoryResponse, status_code=status.HTTP_201_CREATED)
async def create_category(
    data: CategoryCreate,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    existing = await db.execute(select(Category).where(Category.slug == data.slug))
    if existing.scalar_one_or_none():
        raise HTTPException(status_code=400, detail="Slug already exists")
    cat = Category(**data.model_dump())
    db.add(cat)
    await db.commit()
    await db.refresh(cat)
    cat.listing_count = 0
    return cat


@router.put("/{cat_id}", response_model=CategoryResponse)
async def update_category(
    cat_id: int,
    data: CategoryCreate,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Category).where(Category.id == cat_id))
    cat = result.scalar_one_or_none()
    if not cat:
        raise HTTPException(status_code=404, detail="Category not found")
    for key, val in data.model_dump().items():
        setattr(cat, key, val)
    await db.commit()
    await db.refresh(cat)
    cat.listing_count = 0
    return cat


@router.delete("/{cat_id}", status_code=204)
async def delete_category(
    cat_id: int,
    user: User = Depends(require_role("admin")),
    db: AsyncSession = Depends(get_db),
):
    result = await db.execute(select(Category).where(Category.id == cat_id))
    cat = result.scalar_one_or_none()
    if not cat:
        raise HTTPException(status_code=404, detail="Category not found")
    await db.delete(cat)
    await db.commit()
