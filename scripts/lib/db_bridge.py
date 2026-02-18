"""
db_bridge.py
============
Database bridge for the Solar Directory pipeline.
Provides methods to interact with Flynax DB and pipeline state tables.
"""

import hashlib
import json
import logging
from datetime import datetime

logger = logging.getLogger(__name__)


class DatabaseBridge:
    """MySQL bridge using Flynax DB credentials."""

    def __init__(self, config):
        import mysql.connector

        self.prefix = config.db_prefix
        self.conn = mysql.connector.connect(
            host=config.db_host,
            port=config.db_port,
            user=config.db_user,
            password=config.db_pass,
            database=config.db_name,
            charset="utf8mb4",
            autocommit=True,
        )
        self.cursor = self.conn.cursor(dictionary=True)
        logger.info("Database connected: %s@%s/%s", config.db_user, config.db_host, config.db_name)

    def _t(self, table: str) -> str:
        """Prefix a table name."""
        return f"{self.prefix}{table}"

    # ----- Region schedule -----

    def get_regions_for_rotation(self, count: int = 5) -> list:
        """Get the least-recently-scraped enabled regions."""
        sql = f"""
            SELECT region_code, region_name
            FROM `{self._t('solar_region_schedule')}`
            WHERE enabled = 1
            ORDER BY last_scraped_at IS NOT NULL, last_scraped_at ASC, priority DESC
            LIMIT %s
        """
        self.cursor.execute(sql, (count,))
        return self.cursor.fetchall()

    def update_region_schedule(self, region_code: str, total_listings: int = 0):
        """Mark a region as scraped now."""
        sql = f"""
            UPDATE `{self._t('solar_region_schedule')}`
            SET last_scraped_at = NOW(), total_listings = %s
            WHERE region_code = %s
        """
        self.cursor.execute(sql, (total_listings, region_code))
        self.conn.commit()

    # ----- Listing sources -----

    def lookup_by_place_id(self, place_id: str) -> dict | None:
        """Check if a Google Place ID already exists in our system."""
        sql = f"""
            SELECT ID, listing_id, account_id, last_google_rating, last_review_count, business_status
            FROM `{self._t('solar_listing_sources')}`
            WHERE google_place_id = %s
        """
        self.cursor.execute(sql, (place_id,))
        return self.cursor.fetchone()

    def insert_listing_source(self, listing_id: int, account_id: int, place_id: str,
                               rating: float = None, review_count: int = 0,
                               data_hash: str = ""):
        """Register a new listing's source mapping."""
        sql = f"""
            INSERT INTO `{self._t('solar_listing_sources')}`
            (listing_id, account_id, google_place_id, outscraper_hash,
             last_google_rating, last_review_count, last_scraped_at, business_status)
            VALUES (%s, %s, %s, %s, %s, %s, NOW(), 'OPERATIONAL')
        """
        self.cursor.execute(sql, (listing_id, account_id, place_id, data_hash,
                                   rating, review_count))
        self.conn.commit()

    def update_listing_source(self, place_id: str, rating: float = None,
                               review_count: int = 0, status: str = "OPERATIONAL",
                               data_hash: str = ""):
        """Update an existing listing source record."""
        sql = f"""
            UPDATE `{self._t('solar_listing_sources')}`
            SET last_google_rating = %s, last_review_count = %s,
                business_status = %s, outscraper_hash = %s, last_scraped_at = NOW()
            WHERE google_place_id = %s
        """
        self.cursor.execute(sql, (rating, review_count, status, data_hash, place_id))
        self.conn.commit()

    def get_all_place_ids(self, batch_size: int = 1000):
        """Generator yielding batches of listing source records."""
        offset = 0
        while True:
            sql = f"""
                SELECT listing_id, google_place_id, last_google_rating, last_review_count
                FROM `{self._t('solar_listing_sources')}`
                WHERE business_status = 'OPERATIONAL'
                ORDER BY ID
                LIMIT %s OFFSET %s
            """
            self.cursor.execute(sql, (batch_size, offset))
            batch = self.cursor.fetchall()
            if not batch:
                break
            yield batch
            offset += batch_size

    # ----- Listing management -----

    def deactivate_listing(self, listing_id: int):
        """Set a listing status to expired."""
        sql = f"UPDATE `{self._t('listings')}` SET Status = 'expired' WHERE ID = %s"
        self.cursor.execute(sql, (listing_id,))
        self.conn.commit()
        logger.info("Deactivated listing ID %d", listing_id)

    def update_listing_field(self, listing_id: int, field_key: str, value: str):
        """Update a single field in listings_data."""
        # Try update first
        sql = f"""
            UPDATE `{self._t('listings_data')}`
            SET Value = %s
            WHERE Listing_ID = %s AND `Key` = %s
        """
        self.cursor.execute(sql, (value, listing_id, field_key))
        if self.cursor.rowcount == 0:
            # Insert if not exists
            sql = f"""
                INSERT INTO `{self._t('listings_data')}` (Listing_ID, `Key`, Value)
                VALUES (%s, %s, %s)
            """
            self.cursor.execute(sql, (listing_id, field_key, value))
        self.conn.commit()

    # ----- Pipeline runs -----

    def record_pipeline_run(self, run_type: str, region: str = None,
                             log_path: str = None) -> int:
        """Start a new pipeline run record. Returns the run ID."""
        sql = f"""
            INSERT INTO `{self._t('solar_pipeline_runs')}`
            (run_type, region, status, started_at, log_path)
            VALUES (%s, %s, 'running', NOW(), %s)
        """
        self.cursor.execute(sql, (run_type, region, log_path))
        self.conn.commit()
        return self.cursor.lastrowid

    def update_pipeline_run(self, run_id: int, status: str, stats: dict = None):
        """Update a pipeline run with final status and stats."""
        stats = stats or {}
        sql = f"""
            UPDATE `{self._t('solar_pipeline_runs')}`
            SET status = %s, completed_at = NOW(),
                records_scraped = %s, records_new = %s, records_updated = %s,
                records_deactivated = %s, records_errors = %s,
                outscraper_credits_used = %s, error_message = %s
            WHERE ID = %s
        """
        self.cursor.execute(sql, (
            status,
            stats.get("scraped", 0),
            stats.get("new", 0),
            stats.get("updated", 0),
            stats.get("deactivated", 0),
            stats.get("errors", 0),
            stats.get("credits", 0),
            stats.get("error_message"),
            run_id,
        ))
        self.conn.commit()

    def get_monthly_credits_used(self) -> int:
        """Get total Outscraper credits used this calendar month."""
        sql = f"""
            SELECT COALESCE(SUM(outscraper_credits_used), 0) AS total
            FROM `{self._t('solar_pipeline_runs')}`
            WHERE started_at >= DATE_FORMAT(NOW(), '%%Y-%%m-01')
        """
        self.cursor.execute(sql)
        row = self.cursor.fetchone()
        return int(row["total"]) if row else 0

    # ----- Utilities -----

    @staticmethod
    def hash_record(data: dict) -> str:
        """Create a hash of record data for change detection."""
        # Use a subset of fields that matter for change detection
        relevant = {
            "rating": str(data.get("rating", "")),
            "reviews": str(data.get("reviews", "")),
            "phone": str(data.get("phone", "")),
            "site": str(data.get("site", "")),
            "business_status": str(data.get("business_status", "")),
        }
        raw = json.dumps(relevant, sort_keys=True)
        return hashlib.md5(raw.encode()).hexdigest()

    def close(self):
        """Close the database connection."""
        try:
            self.cursor.close()
            self.conn.close()
            logger.info("Database connection closed.")
        except Exception:
            pass
