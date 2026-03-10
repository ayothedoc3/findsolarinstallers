from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.category import Category
from app.models.listing import Listing, ListingCategory
from app.schemas.category import CategoryResponse

router = APIRouter(prefix="/api/categories", tags=["categories"])


@router.get("", response_model=list[CategoryResponse])
async def list_categories(db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    result = await db.execute(
        select(Category).where(Category.is_active == True).order_by(Category.sort_order)
    )
    categories = result.scalars().all()

    items = []
    for cat in categories:
        count_result = await db.execute(
            select(func.count()).select_from(ListingCategory).join(
                Listing, Listing.id == ListingCategory.listing_id
            ).where(
                ListingCategory.category_id == cat.id,
                Listing.status == "active",
                or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
            )
        )
        count = count_result.scalar() or 0
        items.append(CategoryResponse(
            id=cat.id, parent_id=cat.parent_id, name=cat.name, slug=cat.slug,
            description=cat.description, icon=cat.icon, sort_order=cat.sort_order,
            listing_count=count,
        ))
    return items


@router.get("/{slug}", response_model=CategoryResponse)
async def get_category(slug: str, db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    result = await db.execute(select(Category).where(Category.slug == slug, Category.is_active == True))
    cat = result.scalar_one_or_none()
    if not cat:
        raise HTTPException(status_code=404, detail="Category not found")

    count_result = await db.execute(
        select(func.count()).select_from(ListingCategory).join(
            Listing, Listing.id == ListingCategory.listing_id
        ).where(
            ListingCategory.category_id == cat.id,
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
    )
    count = count_result.scalar() or 0
    return CategoryResponse(
        id=cat.id, parent_id=cat.parent_id, name=cat.name, slug=cat.slug,
        description=cat.description, icon=cat.icon, sort_order=cat.sort_order,
        listing_count=count,
    )
