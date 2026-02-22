"""Celery tasks for the pipeline."""
import logging

from celery_app import celery

logger = logging.getLogger(__name__)


@celery.task(name="app.tasks.pipeline_tasks.run_pipeline_task", bind=True)
def run_pipeline_task(self, mode: str, regions: list[str] | None = None, run_id: int | None = None):
    """Execute a pipeline run as a Celery task."""
    from app.pipeline.orchestrator import run_pipeline

    logger.info("Starting pipeline task: mode=%s, regions=%s, run_id=%s", mode, regions, run_id)

    if run_id is None:
        # Create run record if not already created (e.g., from Celery Beat)
        from sqlalchemy import create_engine
        from sqlalchemy.orm import Session
        from app.config import settings
        from app.models.pipeline import PipelineRun

        url = settings.database_url.replace("+asyncpg", "+psycopg2")
        engine = create_engine(url)
        session = Session(engine)
        try:
            run = PipelineRun(mode=mode, regions=regions, status="queued")
            session.add(run)
            session.commit()
            run_id = run.id
        finally:
            session.close()

    try:
        run_pipeline(run_id, mode, regions)
    except Exception as exc:
        logger.error("Pipeline task failed: %s", exc)
        raise
