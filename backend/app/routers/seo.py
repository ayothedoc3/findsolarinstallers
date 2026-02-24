from fastapi import APIRouter, Depends
from fastapi.responses import Response
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import get_db
from app.models.listing import Listing
from app.models.category import Category

router = APIRouter(tags=["seo"])

BASE_URL = "https://findsolarinstallers.xyz"


@router.get("/sitemap.xml", response_class=Response)
async def sitemap(db: AsyncSession = Depends(get_db)):
    urls = []

    # Static pages
    static_pages = [
        ("", "1.0", "daily"),
        ("/search", "0.9", "daily"),
        ("/categories", "0.8", "weekly"),
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
        .where(Listing.status == "active")
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

    xml = f"""<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{chr(10).join(urls)}
</urlset>"""

    return Response(content=xml, media_type="application/xml")
