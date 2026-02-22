"""Celery application configuration."""
from celery import Celery
from celery.schedules import crontab

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
    beat_schedule={
        "weekly-pipeline": {
            "task": "app.tasks.pipeline_tasks.run_pipeline_task",
            "schedule": crontab(hour=2, minute=0, day_of_week=0),
            "args": ("weekly",),
        },
        "monthly-pipeline": {
            "task": "app.tasks.pipeline_tasks.run_pipeline_task",
            "schedule": crontab(hour=3, minute=0, day_of_month="1"),
            "args": ("monthly",),
        },
    },
)
