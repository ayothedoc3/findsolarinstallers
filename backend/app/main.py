from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings


@asynccontextmanager
async def lifespan(app: FastAPI):
    import logging
    logger = logging.getLogger("startup")

    # Auto-create tables on startup
    from sqlalchemy import text, select
    from app.database import engine, async_session, Base
    # Import all models so they register with Base
    from app.models.user import User
    from app.models.listing import Listing  # noqa: F401
    from app.models.category import Category
    from app.models.plan import ListingPlan
    from app.models.contact_request import ContactRequest  # noqa: F401
    from app.models.api_key import ApiKey  # noqa: F401
    from app.models.site_setting import SiteSetting
    from app.models.pipeline import PipelineRun, RegionSchedule, ListingSource  # noqa: F401

    async with engine.begin() as conn:
        await conn.execute(text("CREATE EXTENSION IF NOT EXISTS postgis"))
        await conn.execute(text("CREATE EXTENSION IF NOT EXISTS pg_trgm"))
        await conn.run_sync(Base.metadata.create_all)

    # Seed data if empty
    try:
        from app.utils.security import hash_password
        async with async_session() as db:
            existing = (await db.execute(select(User).limit(1))).scalar_one_or_none()
            if not existing:
                logger.info("Seeding database...")
                # Admin user
                db.add(User(
                    email=settings.admin_email,
                    password_hash=hash_password(settings.admin_password),
                    role="admin", first_name="Admin", is_active=True,
                ))
                # Categories
                root = Category(id=1, name="Solar Installation", slug="solar-installation",
                                description="Solar energy installation services", icon="Sun", sort_order=0)
                db.add(root)
                await db.flush()
                db.add_all([
                    Category(id=2, parent_id=1, name="Residential Solar", slug="residential-solar", icon="Home", sort_order=1),
                    Category(id=3, parent_id=1, name="Commercial Solar", slug="commercial-solar", icon="Building2", sort_order=2),
                    Category(id=4, parent_id=1, name="Solar Maintenance", slug="solar-maintenance", icon="Wrench", sort_order=3),
                    Category(id=5, parent_id=1, name="Solar Battery Storage", slug="solar-battery-storage", icon="Battery", sort_order=4),
                    Category(id=6, parent_id=1, name="Solar Pool Heating", slug="solar-pool-heating", icon="Waves", sort_order=5),
                    Category(id=7, parent_id=1, name="EV Charger + Solar", slug="ev-charger-solar", icon="Zap", sort_order=6),
                ])
                # Plans
                db.add_all([
                    ListingPlan(id=1, name="Free", price_cents=0, interval_days=90, max_images=3, is_featured=False,
                                features=["Basic listing", "3 photos", "90-day visibility"]),
                    ListingPlan(id=2, name="Pro", price_cents=2900, interval_days=365, max_images=10, is_featured=True,
                                features=["10 photos", "Featured badge", "Priority search", "365-day visibility"]),
                    ListingPlan(id=3, name="Premium", price_cents=7900, interval_days=365, max_images=50, is_featured=True,
                                features=["Unlimited photos", "Top placement", "Analytics", "365-day visibility"]),
                ])
                # Region schedule (50 states + DC)
                states = [
                    ("AL","Alabama",5),("AK","Alaska",3),("AZ","Arizona",9),("AR","Arkansas",4),
                    ("CA","California",10),("CO","Colorado",8),("CT","Connecticut",6),("DE","Delaware",4),
                    ("DC","District of Columbia",5),("FL","Florida",10),("GA","Georgia",7),("HI","Hawaii",7),
                    ("ID","Idaho",4),("IL","Illinois",7),("IN","Indiana",5),("IA","Iowa",4),
                    ("KS","Kansas",4),("KY","Kentucky",4),("LA","Louisiana",5),("ME","Maine",5),
                    ("MD","Maryland",6),("MA","Massachusetts",8),("MI","Michigan",6),("MN","Minnesota",6),
                    ("MS","Mississippi",3),("MO","Missouri",5),("MT","Montana",3),("NE","Nebraska",4),
                    ("NV","Nevada",7),("NH","New Hampshire",5),("NJ","New Jersey",8),("NM","New Mexico",6),
                    ("NY","New York",9),("NC","North Carolina",8),("ND","North Dakota",3),("OH","Ohio",7),
                    ("OK","Oklahoma",4),("OR","Oregon",7),("PA","Pennsylvania",7),("RI","Rhode Island",4),
                    ("SC","South Carolina",6),("SD","South Dakota",3),("TN","Tennessee",5),("TX","Texas",10),
                    ("UT","Utah",7),("VT","Vermont",5),("VA","Virginia",7),("WA","Washington",8),
                    ("WV","West Virginia",3),("WI","Wisconsin",5),("WY","Wyoming",3),
                ]
                for code, name, priority in states:
                    db.add(RegionSchedule(state_code=code, state_name=name, priority=priority))
                # Site settings
                db.add_all([
                    SiteSetting(key="site_name", value="SolarListings", type="string"),
                    SiteSetting(key="site_tagline", value="Find Solar Installers Near You", type="string"),
                    SiteSetting(key="contact_email", value="info@findsolarinstallers.xyz", type="string"),
                ])
                await db.commit()
                logger.info("Database seeded successfully!")
            else:
                logger.info("Database already seeded.")

            # Always ensure admin exists and password is synced
            admin = (await db.execute(
                select(User).where(User.email == settings.admin_email)
            )).scalar_one_or_none()
            if admin:
                admin.password_hash = hash_password(settings.admin_password)
                admin.role = "admin"
                await db.commit()
                logger.info(f"Admin password synced (email={settings.admin_email}, pw_len={len(settings.admin_password)})")
    except Exception as e:
        logger.error(f"Seed failed: {e}")

    yield


app = FastAPI(
    title=settings.app_name,
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Import and include routers
from app.routers import auth, listings, categories, contact, search, dashboard
from app.routers.admin import api_keys as admin_api_keys
from app.routers.admin import pipeline as admin_pipeline
from app.routers.admin import listings as admin_listings
from app.routers.admin import users as admin_users
from app.routers.admin import categories as admin_categories
from app.routers.admin import plans as admin_plans
from app.routers.admin import settings as admin_settings
from app.routers.admin import stats as admin_stats

app.include_router(auth.router)
app.include_router(listings.router)
app.include_router(categories.router)
app.include_router(contact.router)
app.include_router(search.router)
app.include_router(dashboard.router)
app.include_router(admin_api_keys.router)
app.include_router(admin_pipeline.router)
app.include_router(admin_listings.router)
app.include_router(admin_users.router)
app.include_router(admin_categories.router)
app.include_router(admin_plans.router)
app.include_router(admin_settings.router)
app.include_router(admin_stats.router)


@app.get("/api/health")
async def health():
    return {"status": "ok"}


