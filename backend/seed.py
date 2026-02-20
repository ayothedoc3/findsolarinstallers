"""Seed the database with initial data."""
import asyncio

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

from app.config import settings
from app.database import async_session, engine, Base
from app.models import *  # noqa
from app.utils.security import hash_password


async def seed():
    # Create tables
    async with engine.begin() as conn:
        # Enable PostGIS
        await conn.execute(text("CREATE EXTENSION IF NOT EXISTS postgis"))
        await conn.execute(text("CREATE EXTENSION IF NOT EXISTS pg_trgm"))
        await conn.run_sync(Base.metadata.drop_all)
        await conn.run_sync(Base.metadata.create_all)

    async with async_session() as db:
        # Create search trigger
        await db.execute(text("""
            CREATE OR REPLACE FUNCTION listings_search_trigger() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english', COALESCE(NEW.name, '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(NEW.city, '') || ' ' || COALESCE(NEW.state, '') || ' ' || array_to_string(COALESCE(NEW.services_offered, '{}'), ' ')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(NEW.description, '')), 'C');
                RETURN NEW;
            END $$ LANGUAGE plpgsql;
        """))
        await db.execute(text("""
            DROP TRIGGER IF EXISTS tsvector_update ON listings;
            CREATE TRIGGER tsvector_update BEFORE INSERT OR UPDATE ON listings
            FOR EACH ROW EXECUTE FUNCTION listings_search_trigger();
        """))

        # Admin user
        admin = User(
            email=settings.admin_email,
            password_hash=hash_password(settings.admin_password),
            role="admin",
            first_name="Admin",
            is_active=True,
        )
        db.add(admin)

        # Categories
        root = Category(id=1, name="Solar Installation", slug="solar-installation", description="Solar energy installation services", icon="Sun", sort_order=0)
        db.add(root)
        await db.flush()

        subcats = [
            Category(id=2, parent_id=1, name="Residential Solar", slug="residential-solar", description="Home solar panel installation", icon="Home", sort_order=1),
            Category(id=3, parent_id=1, name="Commercial Solar", slug="commercial-solar", description="Business and commercial solar solutions", icon="Building2", sort_order=2),
            Category(id=4, parent_id=1, name="Solar Maintenance", slug="solar-maintenance", description="Solar panel maintenance and repair", icon="Wrench", sort_order=3),
            Category(id=5, parent_id=1, name="Solar Battery Storage", slug="solar-battery-storage", description="Battery storage solutions", icon="Battery", sort_order=4),
            Category(id=6, parent_id=1, name="Solar Pool Heating", slug="solar-pool-heating", description="Solar-powered pool heating systems", icon="Waves", sort_order=5),
            Category(id=7, parent_id=1, name="EV Charger + Solar", slug="ev-charger-solar", description="EV charging with solar integration", icon="Zap", sort_order=6),
        ]
        db.add_all(subcats)

        # Listing plans
        plans = [
            ListingPlan(id=1, name="Free", price_cents=0, interval_days=90, max_images=3, is_featured=False, features=["Basic listing", "3 photos", "90-day visibility"]),
            ListingPlan(id=2, name="Pro", price_cents=2900, interval_days=365, max_images=10, is_featured=True, features=["10 photos", "Featured badge", "Priority search", "Lead notifications", "365-day visibility"]),
            ListingPlan(id=3, name="Premium", price_cents=7900, interval_days=365, max_images=50, is_featured=True, features=["Unlimited photos", "Top placement", "Analytics dashboard", "Lead notifications", "Priority support", "365-day visibility"]),
        ]
        db.add_all(plans)

        # Region schedule (all 50 states + DC)
        states = [
            ("AL", "Alabama", 5), ("AK", "Alaska", 3), ("AZ", "Arizona", 9), ("AR", "Arkansas", 4),
            ("CA", "California", 10), ("CO", "Colorado", 8), ("CT", "Connecticut", 6), ("DE", "Delaware", 4),
            ("DC", "District of Columbia", 5), ("FL", "Florida", 10), ("GA", "Georgia", 7), ("HI", "Hawaii", 7),
            ("ID", "Idaho", 4), ("IL", "Illinois", 7), ("IN", "Indiana", 5), ("IA", "Iowa", 4),
            ("KS", "Kansas", 4), ("KY", "Kentucky", 4), ("LA", "Louisiana", 5), ("ME", "Maine", 5),
            ("MD", "Maryland", 6), ("MA", "Massachusetts", 8), ("MI", "Michigan", 6), ("MN", "Minnesota", 6),
            ("MS", "Mississippi", 3), ("MO", "Missouri", 5), ("MT", "Montana", 3), ("NE", "Nebraska", 4),
            ("NV", "Nevada", 7), ("NH", "New Hampshire", 5), ("NJ", "New Jersey", 8), ("NM", "New Mexico", 6),
            ("NY", "New York", 9), ("NC", "North Carolina", 8), ("ND", "North Dakota", 3), ("OH", "Ohio", 7),
            ("OK", "Oklahoma", 4), ("OR", "Oregon", 7), ("PA", "Pennsylvania", 7), ("RI", "Rhode Island", 4),
            ("SC", "South Carolina", 6), ("SD", "South Dakota", 3), ("TN", "Tennessee", 5), ("TX", "Texas", 10),
            ("UT", "Utah", 7), ("VT", "Vermont", 5), ("VA", "Virginia", 7), ("WA", "Washington", 8),
            ("WV", "West Virginia", 3), ("WI", "Wisconsin", 5), ("WY", "Wyoming", 3),
        ]
        for code, name, priority in states:
            db.add(RegionSchedule(state_code=code, state_name=name, priority=priority))

        # Site settings
        site_settings = [
            SiteSetting(key="site_name", value="SolarListings", type="string"),
            SiteSetting(key="site_tagline", value="Find Solar Installers Near You", type="string"),
            SiteSetting(key="contact_email", value="info@findsolarinstallers.xyz", type="string"),
            SiteSetting(key="monthly_credit_budget", value="500", type="number"),
        ]
        db.add_all(site_settings)

        await db.commit()
        print("Database seeded successfully!")


if __name__ == "__main__":
    asyncio.run(seed())
