from datetime import datetime, timezone

from fastapi import APIRouter, Depends
from fastapi.responses import Response
from sqlalchemy import or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.listing import Listing
from app.models.category import Category
from app.models.generated_page import GeneratedPage

router = APIRouter(tags=["seo"])

BASE_URL = "https://findsolarinstallers.xyz"


@router.get("/sitemap.xml", response_class=Response)
async def sitemap(db: AsyncSession = Depends(get_db)):
    now = datetime.now(timezone.utc)
    urls = []

    # Static pages
    static_pages = [
        ("", "1.0", "daily"),
        ("/search", "0.9", "daily"),
        ("/categories", "0.8", "weekly"),
        ("/for-installers", "0.8", "weekly"),
        ("/about", "0.5", "monthly"),
        ("/contact", "0.5", "monthly"),
        ("/privacy", "0.3", "monthly"),
        ("/login", "0.3", "monthly"),
        ("/register", "0.3", "monthly"),
    ]
    for path, priority, freq in static_pages:
        urls.append(f"""  <url>
    <loc>{BASE_URL}{path}</loc>
    <changefreq>{freq}</changefreq>
    <priority>{priority}</priority>
  </url>""")

    # All active listings
    result = await db.execute(
        select(Listing.slug, Listing.updated_at)
        .where(
            Listing.status == "active",
            or_(Listing.expires_at.is_(None), Listing.expires_at >= now),
        )
        .order_by(Listing.updated_at.desc())
    )
    for row in result.all():
        lastmod = row.updated_at.strftime("%Y-%m-%d") if row.updated_at else ""
        lastmod_tag = f"\n    <lastmod>{lastmod}</lastmod>" if lastmod else ""
        urls.append(f"""  <url>
    <loc>{BASE_URL}/listing/{row.slug}</loc>{lastmod_tag}
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>""")

    # pSEO location pages (sorted by hit_count for priority)
    pseo_result = await db.execute(
        select(GeneratedPage.slug, GeneratedPage.updated_at, GeneratedPage.hit_count, GeneratedPage.installer_count)
        .where(GeneratedPage.is_active.is_(True), GeneratedPage.installer_count >= 1)
        .order_by(GeneratedPage.hit_count.desc())
    )
    for row in pseo_result.all():
        lastmod = row.updated_at.strftime("%Y-%m-%d") if row.updated_at else ""
        lastmod_tag = f"\n    <lastmod>{lastmod}</lastmod>" if lastmod else ""
        priority = "0.7" if row.hit_count > 10 else "0.6"
        urls.append(f"""  <url>
    <loc>{BASE_URL}/{row.slug}</loc>{lastmod_tag}
    <changefreq>weekly</changefreq>
    <priority>{priority}</priority>
  </url>""")

    xml = f"""<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{chr(10).join(urls)}
</urlset>"""

    return Response(content=xml, media_type="application/xml")
