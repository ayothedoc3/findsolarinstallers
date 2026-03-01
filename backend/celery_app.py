"""Celery application configuration."""
from celery import Celery

from app.config import settings

celery = Celery(
    "solar_pipeline",
    broker=settings.redis_url,
    backend=settings.redis_url,
    include=["app.tasks.pipeline_tasks"],
)

celery.conf.update(
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="UTC",
    enable_utc=True,
    task_track_started=True,
    task_time_limit=7200,  # 2 hour hard limit
    task_soft_time_limit=6000,  # 100 min soft limit
    worker_prefetch_multiplier=1,
    # No automatic schedule — scraping is triggered manually from the admin dashboard
)
