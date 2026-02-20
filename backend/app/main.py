from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings


@asynccontextmanager
async def lifespan(app: FastAPI):
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
from app.routers import auth, listings, categories, contact, search
from app.routers.admin import api_keys as admin_api_keys
from app.routers.admin import pipeline as admin_pipeline

app.include_router(auth.router)
app.include_router(listings.router)
app.include_router(categories.router)
app.include_router(contact.router)
app.include_router(search.router)
app.include_router(admin_api_keys.router)
app.include_router(admin_pipeline.router)


@app.get("/api/health")
async def health():
    return {"status": "ok"}
