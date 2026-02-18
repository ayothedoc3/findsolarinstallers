#!/usr/bin/env python3
"""
pipeline_orchestrator.py
========================
Master automation script for the Solar Installer Directory pipeline.

Orchestrates:
  - Outscraper Google Maps scraping
  - Data cleaning, verification, enrichment
  - Flynax database import via bulk_import.php / update_listing.php
  - Monthly data freshness checks

Modes:
  --mode backfill   Scrape all 50 US states (or a subset via --regions)
  --mode weekly     Rotate through scheduled regions, sync new/updated listings
  --mode monthly    Re-verify all existing place_ids for freshness

Usage:
    python scripts/pipeline_orchestrator.py --mode weekly
    python scripts/pipeline_orchestrator.py --mode backfill --regions CA,TX,FL
    python scripts/pipeline_orchestrator.py --mode monthly --dry-run
"""

import argparse
import csv
import hashlib
import json
import logging
import os
import re
import subprocess
import sys
import time
from datetime import datetime, timedelta
from pathlib import Path

# ---------------------------------------------------------------------------
# Third-party imports (with graceful fallback)
# ---------------------------------------------------------------------------
try:
    import pandas as pd
except ImportError:
    sys.exit("FATAL: pandas is required. Install with: pip install pandas")

try:
    import mysql.connector
except ImportError:
    sys.exit("FATAL: mysql-connector-python is required. Install with: pip install mysql-connector-python")

try:
    from dotenv import load_dotenv
except ImportError:
    # Provide a no-op fallback so the script can still run if .env is not used
    def load_dotenv(*args, **kwargs):
        pass

try:
    from outscraper import ApiClient as OutscraperApiClient
except ImportError:
    OutscraperApiClient = None

# ---------------------------------------------------------------------------
# Dynamic imports for sibling pipeline scripts (with subprocess fallback)
# ---------------------------------------------------------------------------
_SCRIPTS_DIR = Path(__file__).resolve().parent
if str(_SCRIPTS_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPTS_DIR))

_clean_mod = None
_verify_mod = None
_enrich_mod = None

try:
    from importlib import import_module
    _clean_mod = import_module("01_clean_outscraper_data")
except Exception:
    _clean_mod = None

try:
    _verify_mod = import_module("02_verify_with_crawl4ai")
except Exception:
    _verify_mod = None

try:
    _enrich_mod = import_module("03_enrich_data")
except Exception:
    _enrich_mod = None

# ---------------------------------------------------------------------------
# US state mapping
# ---------------------------------------------------------------------------
US_STATES = {
    "AL": "Alabama", "AK": "Alaska", "AZ": "Arizona", "AR": "Arkansas",
    "CA": "California", "CO": "Colorado", "CT": "Connecticut", "DE": "Delaware",
    "FL": "Florida", "GA": "Georgia", "HI": "Hawaii", "ID": "Idaho",
    "IL": "Illinois", "IN": "Indiana", "IA": "Iowa", "KS": "Kansas",
    "KY": "Kentucky", "LA": "Louisiana", "ME": "Maine", "MD": "Maryland",
    "MA": "Massachusetts", "MI": "Michigan", "MN": "Minnesota", "MS": "Mississippi",
    "MO": "Missouri", "MT": "Montana", "NE": "Nebraska", "NV": "Nevada",
    "NH": "New Hampshire", "NJ": "New Jersey", "NM": "New Mexico", "NY": "New York",
    "NC": "North Carolina", "ND": "North Dakota", "OH": "Ohio", "OK": "Oklahoma",
    "OR": "Oregon", "PA": "Pennsylvania", "RI": "Rhode Island", "SC": "South Carolina",
    "SD": "South Dakota", "TN": "Tennessee", "TX": "Texas", "UT": "Utah",
    "VT": "Vermont", "VA": "Virginia", "WA": "Washington", "WV": "West Virginia",
    "WI": "Wisconsin", "WY": "Wyoming", "DC": "District of Columbia",
}


# ============================================================================
# Configuration
# ============================================================================
class PipelineConfig:
    """Loads and exposes all configuration for the pipeline."""

    def __init__(self):
        self.flynax_root = _SCRIPTS_DIR.parent
        self.scripts_dir = _SCRIPTS_DIR

        # Load scripts/.env if it exists
        env_path = self.scripts_dir / ".env"
        if env_path.exists():
            load_dotenv(dotenv_path=str(env_path))

        # Parse Flynax config.inc.php for database credentials
        self._db_config = self._parse_flynax_config()

        # Database settings
        self.db_host = self._db_config.get("RL_DBHOST", "localhost")
        self.db_port = int(self._db_config.get("RL_DBPORT", 3306))
        self.db_user = self._db_config.get("RL_DBUSER", "root")
        self.db_pass = self._db_config.get("RL_DBPASS", "")
        self.db_name = self._db_config.get("RL_DBNAME", "flynax")
        self.db_prefix = self._db_config.get("RL_DBPREFIX", "fl_")

        # Outscraper
        self.outscraper_api_key = os.getenv("OUTSCRAPER_API_KEY", "")

        # Budget / scheduling
        self.monthly_credit_budget = int(os.getenv("MONTHLY_CREDIT_BUDGET", "50000"))
        self.weekly_region_count = int(os.getenv("WEEKLY_REGION_COUNT", "5"))

        # Directories
        self.data_dir = self.scripts_dir / "data"
        self.log_dir = self.scripts_dir / "logs"
        self.data_dir.mkdir(parents=True, exist_ok=True)
        self.log_dir.mkdir(parents=True, exist_ok=True)

    # ------------------------------------------------------------------
    def _parse_flynax_config(self) -> dict:
        """Parse includes/config.inc.php for RL_DB* constants."""
        config_path = self.flynax_root / "includes" / "config.inc.php"
        result = {}

        if not config_path.exists():
            logging.getLogger(__name__).warning(
                "Flynax config not found at %s. Using defaults / env vars.", config_path
            )
            # Fall back to environment variables
            for key in ("RL_DBHOST", "RL_DBUSER", "RL_DBPASS", "RL_DBNAME", "RL_DBPREFIX", "RL_DBPORT"):
                val = os.getenv(key)
                if val is not None:
                    result[key] = val
            return result

        try:
            content = config_path.read_text(encoding="utf-8", errors="replace")
        except Exception as exc:
            logging.getLogger(__name__).warning("Could not read config.inc.php: %s", exc)
            return result

        # Match string constants: define('RL_DBHOST', 'localhost');
        for match in re.finditer(r"define\('(RL_DB\w+)',\s*'([^']*)'\)", content):
            result[match.group(1)] = match.group(2)

        # Match numeric constants: define('RL_DBPORT', 3306);
        for match in re.finditer(r"define\('(RL_DB\w+)',\s*(\d+)\)", content):
            result[match.group(1)] = match.group(2)

        return result


# ============================================================================
# Database Bridge
# ============================================================================
class DatabaseBridge:
    """Thin wrapper around mysql.connector for pipeline-specific queries."""

    def __init__(self, config: PipelineConfig):
        self._config = config
        self._prefix = config.db_prefix
        self._conn = None
        self._connect()

    # ------------------------------------------------------------------
    def _connect(self):
        try:
            self._conn = mysql.connector.connect(
                host=self._config.db_host,
                port=self._config.db_port,
                user=self._config.db_user,
                password=self._config.db_pass,
                database=self._config.db_name,
                charset="utf8mb4",
                autocommit=True,
                connection_timeout=30,
            )
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("Database connection failed: %s", exc)
            raise

    def _ensure_connection(self):
        """Reconnect if the connection has dropped."""
        try:
            if self._conn is None or not self._conn.is_connected():
                self._connect()
        except Exception:
            self._connect()

    # ------------------------------------------------------------------
    # Region scheduling
    # ------------------------------------------------------------------
    def get_regions_for_rotation(self, count: int = 5) -> list:
        """Return the next *count* regions due for scraping."""
        self._ensure_connection()
        cursor = self._conn.cursor(dictionary=True)
        try:
            sql = (
                f"SELECT region_code, region_name "
                f"FROM `{self._prefix}solar_region_schedule` "
                f"WHERE enabled = 1 "
                f"ORDER BY last_scraped_at IS NULL DESC, last_scraped_at ASC, priority DESC "
                f"LIMIT %s"
            )
            cursor.execute(sql, (count,))
            return cursor.fetchall()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("get_regions_for_rotation failed: %s", exc)
            return []
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Listing source lookups
    # ------------------------------------------------------------------
    def lookup_by_place_id(self, place_id: str) -> dict | None:
        """Look up a listing source by its Google place_id."""
        self._ensure_connection()
        cursor = self._conn.cursor(dictionary=True)
        try:
            sql = (
                f"SELECT * FROM `{self._prefix}solar_listing_sources` "
                f"WHERE google_place_id = %s LIMIT 1"
            )
            cursor.execute(sql, (place_id,))
            row = cursor.fetchone()
            return row
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("lookup_by_place_id failed: %s", exc)
            return None
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Pipeline runs
    # ------------------------------------------------------------------
    def record_pipeline_run(self, run_type: str, region: str, status: str, stats: dict) -> int:
        """Insert a new pipeline run record and return its ID."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = (
                f"INSERT INTO `{self._prefix}solar_pipeline_runs` "
                f"(run_type, region, status, stats_json, started_at) "
                f"VALUES (%s, %s, %s, %s, NOW())"
            )
            cursor.execute(sql, (run_type, region, status, json.dumps(stats)))
            self._conn.commit()
            return cursor.lastrowid
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("record_pipeline_run failed: %s", exc)
            return 0
        finally:
            cursor.close()

    def update_pipeline_run(self, run_id: int, status: str, stats: dict):
        """Update an existing pipeline run with final status and stats."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = (
                f"UPDATE `{self._prefix}solar_pipeline_runs` "
                f"SET status = %s, stats_json = %s, "
                f"    outscraper_credits_used = %s, "
                f"    completed_at = NOW() "
                f"WHERE id = %s"
            )
            credits = stats.get("outscraper_credits_used", 0)
            cursor.execute(sql, (status, json.dumps(stats), credits, run_id))
            self._conn.commit()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("update_pipeline_run failed: %s", exc)
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Listing sources CRUD
    # ------------------------------------------------------------------
    def insert_listing_source(self, listing_id: int, place_id: str, rating: float, review_count: int):
        """Register a new listing source after import."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = (
                f"INSERT INTO `{self._prefix}solar_listing_sources` "
                f"(listing_id, google_place_id, last_google_rating, last_review_count, "
                f" status, created_at, updated_at) "
                f"VALUES (%s, %s, %s, %s, 'active', NOW(), NOW())"
            )
            cursor.execute(sql, (listing_id, place_id, rating, review_count))
            self._conn.commit()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("insert_listing_source failed: %s", exc)
        finally:
            cursor.close()

    def update_listing_source(self, place_id: str, rating: float, review_count: int, status: str = "active"):
        """Update an existing listing source with fresh data."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = (
                f"UPDATE `{self._prefix}solar_listing_sources` "
                f"SET last_google_rating = %s, last_review_count = %s, "
                f"    status = %s, updated_at = NOW() "
                f"WHERE google_place_id = %s"
            )
            cursor.execute(sql, (rating, review_count, status, place_id))
            self._conn.commit()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("update_listing_source failed: %s", exc)
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Region schedule
    # ------------------------------------------------------------------
    def update_region_schedule(self, region_code: str, total_listings: int):
        """Mark a region as freshly scraped."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = (
                f"UPDATE `{self._prefix}solar_region_schedule` "
                f"SET last_scraped_at = NOW(), total_listings = %s "
                f"WHERE region_code = %s"
            )
            cursor.execute(sql, (total_listings, region_code))
            self._conn.commit()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("update_region_schedule failed: %s", exc)
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Listing deactivation
    # ------------------------------------------------------------------
    def deactivate_listing(self, listing_id: int):
        """Mark a Flynax listing as expired (permanently closed business)."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            sql = f"UPDATE `{self._prefix}listings` SET `Status` = 'expired' WHERE `ID` = %s"
            cursor.execute(sql, (listing_id,))
            self._conn.commit()
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("deactivate_listing failed: %s", exc)
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Budget tracking
    # ------------------------------------------------------------------
    def get_monthly_credits_used(self) -> int:
        """Return total Outscraper credits consumed this calendar month."""
        self._ensure_connection()
        cursor = self._conn.cursor()
        try:
            first_of_month = datetime.now().replace(day=1).strftime("%Y-%m-%d 00:00:00")
            sql = (
                f"SELECT COALESCE(SUM(outscraper_credits_used), 0) "
                f"FROM `{self._prefix}solar_pipeline_runs` "
                f"WHERE started_at >= %s"
            )
            cursor.execute(sql, (first_of_month,))
            row = cursor.fetchone()
            return int(row[0]) if row else 0
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("get_monthly_credits_used failed: %s", exc)
            return 0
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    # Batch place_id retrieval (generator)
    # ------------------------------------------------------------------
    def get_all_place_ids(self, batch_size: int = 1000):
        """
        Yield batches of (listing_id, google_place_id, last_google_rating, last_review_count)
        from solar_listing_sources.
        """
        self._ensure_connection()
        cursor = self._conn.cursor(dictionary=True)
        try:
            offset = 0
            while True:
                sql = (
                    f"SELECT listing_id, google_place_id, last_google_rating, last_review_count "
                    f"FROM `{self._prefix}solar_listing_sources` "
                    f"WHERE status = 'active' "
                    f"ORDER BY listing_id "
                    f"LIMIT %s OFFSET %s"
                )
                cursor.execute(sql, (batch_size, offset))
                rows = cursor.fetchall()
                if not rows:
                    break
                yield rows
                offset += batch_size
        except mysql.connector.Error as exc:
            logging.getLogger(__name__).error("get_all_place_ids failed: %s", exc)
        finally:
            cursor.close()

    # ------------------------------------------------------------------
    def close(self):
        """Close the database connection."""
        if self._conn and self._conn.is_connected():
            self._conn.close()


# ============================================================================
# Outscraper Client Wrapper
# ============================================================================
class SolarOutscraperClient:
    """Wraps the Outscraper SDK for solar-specific Google Maps queries."""

    def __init__(self, api_key: str, monthly_budget: int = 50000):
        if not api_key:
            raise ValueError("Outscraper API key is required. Set OUTSCRAPER_API_KEY in scripts/.env")
        if OutscraperApiClient is None:
            raise ImportError("outscraper package not installed. Install with: pip install outscraper")
        self._client = OutscraperApiClient(api_key=api_key)
        self._monthly_budget = monthly_budget
        self._credits_used = 0
        self._logger = logging.getLogger(self.__class__.__name__)

    # ------------------------------------------------------------------
    def scrape_region(self, state_name: str, queries: list | None = None) -> list:
        """
        Scrape solar installer data for a US state via Outscraper Google Maps API.
        Returns a list of dicts (one per business).
        """
        if queries is None:
            queries = [
                f"solar panel installation companies in {state_name}",
                f"solar installer in {state_name}",
            ]

        all_results = []
        seen_place_ids = set()

        fields = [
            "name", "full_address", "city", "state", "phone", "site",
            "rating", "reviews", "business_status", "place_id",
            "latitude", "longitude", "type", "subtypes",
        ]

        for query in queries:
            self._logger.info("Outscraper query: %s", query)
            try:
                results = self._client.google_maps_search(
                    query,
                    limit=500,
                    language="en",
                    region="US",
                    fields=fields,
                )

                # Outscraper returns a list of lists; flatten
                if results and isinstance(results, list):
                    for batch in results:
                        if isinstance(batch, list):
                            for record in batch:
                                pid = record.get("place_id", "")
                                if pid and pid not in seen_place_ids:
                                    seen_place_ids.add(pid)
                                    all_results.append(record)
                        elif isinstance(batch, dict):
                            pid = batch.get("place_id", "")
                            if pid and pid not in seen_place_ids:
                                seen_place_ids.add(pid)
                                all_results.append(batch)

                # Estimate credits: roughly 1 credit per result
                batch_credits = len(all_results)
                self._credits_used += batch_credits
                self._logger.info(
                    "  -> %d results for query (total unique: %d, credits this session: %d)",
                    len(all_results), len(seen_place_ids), self._credits_used,
                )

            except Exception as exc:
                self._logger.error("Outscraper scrape_region failed for '%s': %s", query, exc)

        return all_results

    # ------------------------------------------------------------------
    def enrich_by_place_ids(self, place_ids: list) -> list:
        """
        Re-fetch data for a list of place_ids (max 20 per call).
        Returns updated records.
        """
        results = []
        # Process in chunks of 20
        for i in range(0, len(place_ids), 20):
            chunk = place_ids[i:i + 20]
            self._logger.info("Enriching %d place_ids (batch %d)...", len(chunk), i // 20 + 1)
            try:
                response = self._client.google_maps_search(
                    chunk,
                    language="en",
                    region="US",
                )
                if response and isinstance(response, list):
                    for batch in response:
                        if isinstance(batch, list):
                            results.extend(batch)
                        elif isinstance(batch, dict):
                            results.append(batch)

                self._credits_used += len(chunk)
            except Exception as exc:
                self._logger.error("enrich_by_place_ids failed: %s", exc)

            # Be polite to the API
            time.sleep(1)

        return results

    # ------------------------------------------------------------------
    def get_credits_used(self) -> int:
        """Return cumulative credits used in this session."""
        return self._credits_used


# ============================================================================
# Pipeline
# ============================================================================
class Pipeline:
    """Orchestrates the end-to-end scraping/cleaning/importing pipeline."""

    def __init__(self, config: PipelineConfig, db: DatabaseBridge, client: SolarOutscraperClient | None):
        self.config = config
        self.db = db
        self.client = client
        self.logger = logging.getLogger(self.__class__.__name__)
        self.dry_run = False
        self._region_errors = {}

    # ------------------------------------------------------------------
    # Helpers
    # ------------------------------------------------------------------
    def _save_csv(self, records: list, filename: str) -> Path:
        """Save a list of dicts to a CSV file in data_dir. Returns the path."""
        path = self.config.data_dir / filename
        if not records:
            self.logger.warning("No records to save for %s", filename)
            return path

        df = pd.DataFrame(records)
        df.to_csv(str(path), index=False)
        self.logger.info("Saved %d records to %s", len(records), path)
        return path

    def _clean_dataframe(self, df: pd.DataFrame) -> pd.DataFrame:
        """Run the cleaning pipeline on a DataFrame."""
        if _clean_mod and hasattr(_clean_mod, "clean_data"):
            # The clean_data function writes to disk; we use a temp approach
            tmp_in = self.config.data_dir / "_tmp_raw.csv"
            tmp_out = self.config.data_dir / "_tmp_cleaned.csv"
            df.to_csv(str(tmp_in), index=False)
            _clean_mod.clean_data(str(tmp_in), str(tmp_out))
            if tmp_out.exists():
                result = pd.read_csv(str(tmp_out), low_memory=False)
                # Cleanup temp files
                tmp_in.unlink(missing_ok=True)
                tmp_out.unlink(missing_ok=True)
                return result
            return df
        else:
            # Fallback: call the script as a subprocess
            self.logger.info("Falling back to subprocess for cleaning...")
            tmp_in = self.config.data_dir / "_tmp_raw.csv"
            tmp_out = self.config.data_dir / "_tmp_cleaned.csv"
            df.to_csv(str(tmp_in), index=False)
            script_path = self.config.scripts_dir / "01_clean_outscraper_data.py"
            if script_path.exists():
                subprocess.run(
                    [sys.executable, str(script_path), "--input", str(tmp_in), "--output", str(tmp_out)],
                    check=False,
                )
                if tmp_out.exists():
                    result = pd.read_csv(str(tmp_out), low_memory=False)
                    tmp_in.unlink(missing_ok=True)
                    tmp_out.unlink(missing_ok=True)
                    return result
            return df

    def _verify_dataframe(self, df: pd.DataFrame) -> pd.DataFrame:
        """Run website verification on a DataFrame."""
        if _verify_mod and hasattr(_verify_mod, "analyze_page_content"):
            # Apply analyze_page_content to each row's website text
            # Since we don't have crawled content here, just pass through
            # The full verification needs the async crawl pipeline
            self.logger.info("Verification module loaded but skipping crawl in orchestrator context.")
            return df
        else:
            # Fallback: call the script as a subprocess
            self.logger.info("Falling back to subprocess for verification...")
            tmp_in = self.config.data_dir / "_tmp_cleaned.csv"
            tmp_out = self.config.data_dir / "_tmp_verified.csv"
            df.to_csv(str(tmp_in), index=False)
            script_path = self.config.scripts_dir / "02_verify_with_crawl4ai.py"
            if script_path.exists():
                subprocess.run(
                    [sys.executable, str(script_path), "--input", str(tmp_in), "--output", str(tmp_out)],
                    check=False,
                )
                if tmp_out.exists():
                    result = pd.read_csv(str(tmp_out), low_memory=False)
                    tmp_in.unlink(missing_ok=True)
                    tmp_out.unlink(missing_ok=True)
                    return result
            return df

    def _enrich_dataframe(self, df: pd.DataFrame) -> pd.DataFrame:
        """Run the enrichment pipeline on a DataFrame."""
        if _enrich_mod and hasattr(_enrich_mod, "enrich_data"):
            tmp_in = self.config.data_dir / "_tmp_verified.csv"
            tmp_out = self.config.data_dir / "_tmp_enriched.csv"
            df.to_csv(str(tmp_in), index=False)
            _enrich_mod.enrich_data(str(tmp_in), str(tmp_out))
            if tmp_out.exists():
                result = pd.read_csv(str(tmp_out), low_memory=False)
                tmp_in.unlink(missing_ok=True)
                tmp_out.unlink(missing_ok=True)
                return result
            return df
        else:
            # Fallback: call the script as a subprocess
            self.logger.info("Falling back to subprocess for enrichment...")
            tmp_in = self.config.data_dir / "_tmp_verified.csv"
            tmp_out = self.config.data_dir / "_tmp_enriched.csv"
            df.to_csv(str(tmp_in), index=False)
            script_path = self.config.scripts_dir / "03_enrich_data.py"
            if script_path.exists():
                subprocess.run(
                    [sys.executable, str(script_path), "--input", str(tmp_in), "--output", str(tmp_out)],
                    check=False,
                )
                if tmp_out.exists():
                    result = pd.read_csv(str(tmp_out), low_memory=False)
                    tmp_in.unlink(missing_ok=True)
                    tmp_out.unlink(missing_ok=True)
                    return result
            return df

    def _run_php_import(self, csv_path: Path, batch_size: int = 500) -> bool:
        """Execute bulk_import.php against a CSV file."""
        php_script = self.config.flynax_root / "scripts" / "bulk_import.php"
        if not php_script.exists():
            self.logger.error("bulk_import.php not found at %s", php_script)
            return False

        if self.dry_run:
            self.logger.info("[DRY RUN] Would run: php %s --input %s --batch-size %d",
                             php_script, csv_path, batch_size)
            return True

        self.logger.info("Running bulk_import.php for %s ...", csv_path)
        result = subprocess.run(
            ["php", str(php_script), "--input", str(csv_path), "--batch-size", str(batch_size)],
            capture_output=True, text=True, timeout=3600,
        )
        if result.returncode != 0:
            self.logger.error("bulk_import.php failed (exit %d): %s", result.returncode, result.stderr)
            return False

        self.logger.info("bulk_import.php output:\n%s", result.stdout[-2000:] if len(result.stdout) > 2000 else result.stdout)
        return True

    def _run_php_update(self, csv_path: Path) -> bool:
        """Execute update_listing.php against a CSV file."""
        php_script = self.config.flynax_root / "scripts" / "update_listing.php"
        if not php_script.exists():
            self.logger.warning("update_listing.php not found at %s. Skipping updates via PHP.", php_script)
            return False

        if self.dry_run:
            self.logger.info("[DRY RUN] Would run: php %s --input %s", php_script, csv_path)
            return True

        self.logger.info("Running update_listing.php for %s ...", csv_path)
        result = subprocess.run(
            ["php", str(php_script), "--input", str(csv_path)],
            capture_output=True, text=True, timeout=3600,
        )
        if result.returncode != 0:
            self.logger.error("update_listing.php failed (exit %d): %s", result.returncode, result.stderr)
            return False

        self.logger.info("update_listing.php output:\n%s", result.stdout[-2000:] if len(result.stdout) > 2000 else result.stdout)
        return True

    # ------------------------------------------------------------------
    # Process a single region (shared logic for backfill and weekly)
    # ------------------------------------------------------------------
    def _process_region(self, region_code: str, state_name: str, run_type: str) -> dict:
        """
        Scrape, clean, verify, enrich, and import data for a single region.
        Returns a stats dict.
        """
        stats = {
            "region": region_code,
            "state_name": state_name,
            "raw_count": 0,
            "cleaned_count": 0,
            "new_count": 0,
            "updated_count": 0,
            "deactivated_count": 0,
            "outscraper_credits_used": 0,
            "status": "running",
        }

        run_id = self.db.record_pipeline_run(run_type, region_code, "running", stats)
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")

        try:
            # ----- Step 1: Scrape -----
            self.logger.info("Scraping data for %s (%s)...", state_name, region_code)
            if self.client is None:
                raise RuntimeError("Outscraper client is not configured.")
            raw_records = self.client.scrape_region(state_name)
            stats["raw_count"] = len(raw_records)
            stats["outscraper_credits_used"] = self.client.get_credits_used()
            self.logger.info("  Scraped %d raw records for %s", len(raw_records), state_name)

            if not raw_records:
                self.logger.warning("  No records returned for %s. Skipping.", state_name)
                stats["status"] = "completed_empty"
                self.db.update_pipeline_run(run_id, "completed_empty", stats)
                return stats

            # Save raw data
            raw_csv = f"raw_{region_code}_{timestamp}.csv"
            raw_path = self._save_csv(raw_records, raw_csv)

            # ----- Step 2: Clean -----
            self.logger.info("Cleaning data for %s...", region_code)
            df_raw = pd.DataFrame(raw_records)
            df_cleaned = self._clean_dataframe(df_raw)
            stats["cleaned_count"] = len(df_cleaned)
            self.logger.info("  Cleaned: %d -> %d records", stats["raw_count"], stats["cleaned_count"])

            # ----- Step 3: Verify -----
            self.logger.info("Verifying data for %s...", region_code)
            df_verified = self._verify_dataframe(df_cleaned)

            # ----- Step 4: Enrich -----
            self.logger.info("Enriching data for %s...", region_code)
            df_enriched = self._enrich_dataframe(df_verified)

            # ----- Step 5: Diff against existing data -----
            new_records = []
            update_records = []

            for _, row in df_enriched.iterrows():
                place_id = str(row.get("place_id", "")).strip()
                if not place_id:
                    # No place_id means it's new by default
                    new_records.append(row.to_dict())
                    continue

                existing = self.db.lookup_by_place_id(place_id)
                if existing is None:
                    new_records.append(row.to_dict())
                else:
                    # Check for changes
                    current_rating = float(existing.get("last_google_rating", 0) or 0)
                    current_reviews = int(existing.get("last_review_count", 0) or 0)
                    new_rating = float(row.get("google_rating", 0) or 0)
                    new_reviews = int(float(row.get("total_reviews", 0) or 0))
                    biz_status = str(row.get("business_status", "")).strip().upper()

                    rating_changed = abs(current_rating - new_rating) > 0.01
                    reviews_changed = current_reviews != new_reviews
                    is_closed = biz_status == "CLOSED_PERMANENTLY"

                    if rating_changed or reviews_changed or is_closed:
                        record = row.to_dict()
                        record["_existing_listing_id"] = existing.get("listing_id")
                        record["_existing_place_id"] = place_id
                        update_records.append(record)

                        if is_closed and not self.dry_run:
                            lid = existing.get("listing_id")
                            if lid:
                                self.db.deactivate_listing(int(lid))
                                stats["deactivated_count"] += 1
                        elif not self.dry_run:
                            self.db.update_listing_source(place_id, new_rating, new_reviews, "active")

            stats["new_count"] = len(new_records)
            stats["updated_count"] = len(update_records)
            self.logger.info("  New: %d | Updated: %d | Deactivated: %d",
                             stats["new_count"], stats["updated_count"], stats["deactivated_count"])

            # ----- Step 6: Import new records via PHP -----
            if new_records:
                new_csv_name = f"new_{region_code}_{timestamp}.csv"
                new_csv_path = self._save_csv(new_records, new_csv_name)
                import_ok = self._run_php_import(new_csv_path, batch_size=500)

                if import_ok and not self.dry_run:
                    # Register new sources in solar_listing_sources
                    for rec in new_records:
                        pid = str(rec.get("place_id", "")).strip()
                        if pid:
                            rating = float(rec.get("google_rating", 0) or 0)
                            reviews = int(float(rec.get("total_reviews", 0) or 0))
                            # listing_id would ideally come from the PHP import output
                            # For now, use 0 as a placeholder; a post-import reconciliation
                            # step can match by place_id
                            self.db.insert_listing_source(0, pid, rating, reviews)

            # ----- Step 7: Update existing records -----
            if update_records:
                update_csv_name = f"updates_{region_code}_{timestamp}.csv"
                update_csv_path = self._save_csv(update_records, update_csv_name)
                self._run_php_update(update_csv_path)

            # ----- Step 8: Update region schedule -----
            if not self.dry_run:
                self.db.update_region_schedule(region_code, stats["cleaned_count"])

            stats["status"] = "completed"
            self.db.update_pipeline_run(run_id, "completed", stats)

        except Exception as exc:
            self.logger.error("Region %s failed: %s", region_code, exc, exc_info=True)
            stats["status"] = "failed"
            stats["error"] = str(exc)
            self.db.update_pipeline_run(run_id, "failed", stats)
            self._region_errors[region_code] = str(exc)

        return stats

    # ------------------------------------------------------------------
    # Backfill mode
    # ------------------------------------------------------------------
    def run_backfill(self, regions: list | None = None):
        """
        Scrape all 50 US states + DC (or a subset) and bulk-import everything.
        """
        if regions:
            state_codes = [r.strip().upper() for r in regions]
            state_map = {code: US_STATES.get(code, code) for code in state_codes}
        else:
            state_map = dict(US_STATES)

        self.logger.info("=" * 70)
        self.logger.info("BACKFILL MODE: Processing %d regions", len(state_map))
        self.logger.info("=" * 70)

        all_stats = []
        total_regions = len(state_map)
        failed_count = 0

        for idx, (code, name) in enumerate(state_map.items(), 1):
            self.logger.info("-" * 60)
            self.logger.info("[%d/%d] Processing %s (%s)", idx, total_regions, name, code)
            self.logger.info("-" * 60)

            try:
                stats = self._process_region(code, name, "backfill")
                all_stats.append(stats)
                if stats.get("status") == "failed":
                    failed_count += 1
            except Exception as exc:
                self.logger.error("Unexpected error for %s: %s", code, exc, exc_info=True)
                failed_count += 1
                self._region_errors[code] = str(exc)

            # Brief pause between regions to avoid rate-limiting
            if idx < total_regions:
                time.sleep(2)

        self._print_summary("BACKFILL", all_stats, failed_count, total_regions)

        if failed_count > total_regions / 2:
            self.logger.error("More than 50%% of regions failed. Exiting with code 2.")
            return 2
        return 0

    # ------------------------------------------------------------------
    # Weekly mode
    # ------------------------------------------------------------------
    def run_weekly(self):
        """
        Rotate through scheduled regions, scrape, diff, and import changes.
        """
        self.logger.info("=" * 70)
        self.logger.info("WEEKLY MODE: Fetching next %d regions from schedule", self.config.weekly_region_count)
        self.logger.info("=" * 70)

        # Check budget
        db_credits = self.db.get_monthly_credits_used()
        session_credits = self.client.get_credits_used() if self.client else 0
        total_credits = db_credits + session_credits

        self.logger.info("Monthly credit usage: %d / %d (%.1f%%)",
                         total_credits, self.config.monthly_credit_budget,
                         (total_credits / self.config.monthly_credit_budget * 100) if self.config.monthly_credit_budget else 0)

        if total_credits >= self.config.monthly_credit_budget:
            self.logger.warning("Monthly credit budget exhausted. Aborting weekly run.")
            return 0

        # Fetch regions
        regions = self.db.get_regions_for_rotation(self.config.weekly_region_count)
        if not regions:
            self.logger.warning("No regions returned from schedule. Check solar_region_schedule table.")
            return 0

        self.logger.info("Regions to process: %s",
                         ", ".join(f"{r['region_code']} ({r['region_name']})" for r in regions))

        all_stats = []
        total_regions = len(regions)
        failed_count = 0

        for idx, region in enumerate(regions, 1):
            code = region["region_code"]
            name = region["region_name"]

            # Re-check budget before each region
            current_credits = self.db.get_monthly_credits_used() + (self.client.get_credits_used() if self.client else 0)
            if current_credits >= self.config.monthly_credit_budget:
                self.logger.warning("Monthly budget reached after %d regions. Stopping.", idx - 1)
                break

            self.logger.info("-" * 60)
            self.logger.info("[%d/%d] Processing %s (%s)", idx, total_regions, name, code)
            self.logger.info("-" * 60)

            try:
                stats = self._process_region(code, name, "weekly")
                all_stats.append(stats)
                if stats.get("status") == "failed":
                    failed_count += 1
            except Exception as exc:
                self.logger.error("Unexpected error for %s: %s", code, exc, exc_info=True)
                failed_count += 1
                self._region_errors[code] = str(exc)

            # Pause between regions
            if idx < total_regions:
                time.sleep(2)

        self._print_summary("WEEKLY", all_stats, failed_count, total_regions)

        if total_regions > 0 and failed_count > total_regions / 2:
            self.logger.error("More than 50%% of regions failed. Exiting with code 2.")
            return 2
        return 0

    # ------------------------------------------------------------------
    # Monthly mode
    # ------------------------------------------------------------------
    def run_monthly(self):
        """
        Re-verify all existing place_ids for freshness.
        Updates ratings, review counts, and deactivates closed businesses.
        """
        self.logger.info("=" * 70)
        self.logger.info("MONTHLY MODE: Refreshing all listing sources")
        self.logger.info("=" * 70)

        stats = {
            "total_checked": 0,
            "updated": 0,
            "deactivated": 0,
            "unchanged": 0,
            "errors": 0,
            "outscraper_credits_used": 0,
        }

        run_id = self.db.record_pipeline_run("monthly", "ALL", "running", stats)

        try:
            batch_num = 0
            for batch in self.db.get_all_place_ids(batch_size=1000):
                batch_num += 1
                self.logger.info("Processing batch %d (%d records)...", batch_num, len(batch))

                # Extract place_ids for this batch
                place_ids = [row["google_place_id"] for row in batch if row.get("google_place_id")]
                if not place_ids:
                    continue

                # Process in sub-batches of 20 (Outscraper limit)
                for i in range(0, len(place_ids), 20):
                    chunk_ids = place_ids[i:i + 20]
                    chunk_rows = {row["google_place_id"]: row for row in batch if row.get("google_place_id") in chunk_ids}

                    if self.dry_run:
                        self.logger.info("[DRY RUN] Would enrich %d place_ids", len(chunk_ids))
                        stats["total_checked"] += len(chunk_ids)
                        continue

                    if self.client is None:
                        self.logger.error("Outscraper client not configured. Cannot refresh.")
                        break

                    try:
                        enriched = self.client.enrich_by_place_ids(chunk_ids)
                        stats["outscraper_credits_used"] = self.client.get_credits_used()
                    except Exception as exc:
                        self.logger.error("Enrich batch failed: %s", exc)
                        stats["errors"] += len(chunk_ids)
                        continue

                    # Build lookup from enriched results
                    enriched_map = {}
                    for rec in enriched:
                        pid = rec.get("place_id", "")
                        if pid:
                            enriched_map[pid] = rec

                    # Compare and update
                    for pid in chunk_ids:
                        stats["total_checked"] += 1
                        existing = chunk_rows.get(pid)
                        fresh = enriched_map.get(pid)

                        if not existing or not fresh:
                            stats["unchanged"] += 1
                            continue

                        current_rating = float(existing.get("last_google_rating", 0) or 0)
                        current_reviews = int(existing.get("last_review_count", 0) or 0)
                        new_rating = float(fresh.get("rating", 0) or 0)
                        new_reviews = int(float(fresh.get("reviews", 0) or 0))
                        biz_status = str(fresh.get("business_status", "")).strip().upper()

                        is_closed = biz_status == "CLOSED_PERMANENTLY"
                        rating_changed = abs(current_rating - new_rating) > 0.01
                        reviews_changed = current_reviews != new_reviews

                        if is_closed:
                            lid = existing.get("listing_id")
                            if lid:
                                self.db.deactivate_listing(int(lid))
                            self.db.update_listing_source(pid, new_rating, new_reviews, "closed")
                            stats["deactivated"] += 1
                        elif rating_changed or reviews_changed:
                            self.db.update_listing_source(pid, new_rating, new_reviews, "active")
                            stats["updated"] += 1
                        else:
                            stats["unchanged"] += 1

                    # Rate-limit between Outscraper calls
                    time.sleep(1)

            stats["status"] = "completed"
            self.db.update_pipeline_run(run_id, "completed", stats)

        except Exception as exc:
            self.logger.error("Monthly refresh failed: %s", exc, exc_info=True)
            stats["status"] = "failed"
            stats["error"] = str(exc)
            self.db.update_pipeline_run(run_id, "failed", stats)

        # Print summary
        self.logger.info("=" * 70)
        self.logger.info("MONTHLY REFRESH SUMMARY")
        self.logger.info("=" * 70)
        self.logger.info("  Total checked:   %d", stats["total_checked"])
        self.logger.info("  Updated:         %d", stats["updated"])
        self.logger.info("  Deactivated:     %d", stats["deactivated"])
        self.logger.info("  Unchanged:       %d", stats["unchanged"])
        self.logger.info("  Errors:          %d", stats["errors"])
        self.logger.info("  Credits used:    %d", stats["outscraper_credits_used"])
        self.logger.info("=" * 70)

        return 0

    # ------------------------------------------------------------------
    def _print_summary(self, mode: str, all_stats: list, failed_count: int, total_regions: int):
        """Print a consolidated summary of a backfill or weekly run."""
        total_raw = sum(s.get("raw_count", 0) for s in all_stats)
        total_cleaned = sum(s.get("cleaned_count", 0) for s in all_stats)
        total_new = sum(s.get("new_count", 0) for s in all_stats)
        total_updated = sum(s.get("updated_count", 0) for s in all_stats)
        total_deactivated = sum(s.get("deactivated_count", 0) for s in all_stats)

        self.logger.info("=" * 70)
        self.logger.info("%s RUN SUMMARY", mode)
        self.logger.info("=" * 70)
        self.logger.info("  Regions processed:  %d / %d", len(all_stats), total_regions)
        self.logger.info("  Regions failed:     %d", failed_count)
        self.logger.info("  Raw records:        %d", total_raw)
        self.logger.info("  Cleaned records:    %d", total_cleaned)
        self.logger.info("  New listings:       %d", total_new)
        self.logger.info("  Updated listings:   %d", total_updated)
        self.logger.info("  Deactivated:        %d", total_deactivated)
        if self.client:
            self.logger.info("  Credits used:       %d", self.client.get_credits_used())
        self.logger.info("=" * 70)

        if self._region_errors:
            self.logger.info("FAILED REGIONS:")
            for code, err in self._region_errors.items():
                self.logger.info("  %s: %s", code, err[:200])
            self.logger.info("=" * 70)


# ============================================================================
# Logging setup
# ============================================================================
def setup_logging(log_dir: Path, verbose: bool = False):
    """Configure logging with both file and console handlers."""
    log_dir.mkdir(parents=True, exist_ok=True)
    log_file = log_dir / f"pipeline_{datetime.now().strftime('%Y-%m-%d')}.log"

    root_logger = logging.getLogger()
    root_logger.setLevel(logging.DEBUG)

    # Remove existing handlers to avoid duplicates on re-run
    root_logger.handlers.clear()

    # File handler — DEBUG level
    fh = logging.FileHandler(str(log_file), encoding="utf-8")
    fh.setLevel(logging.DEBUG)
    fh.setFormatter(logging.Formatter(
        "%(asctime)s [%(levelname)s] %(name)s: %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    ))
    root_logger.addHandler(fh)

    # Console handler — INFO (or DEBUG if verbose)
    ch = logging.StreamHandler(sys.stdout)
    ch.setLevel(logging.DEBUG if verbose else logging.INFO)
    ch.setFormatter(logging.Formatter(
        "%(asctime)s [%(levelname)s] %(message)s",
        datefmt="%H:%M:%S",
    ))
    root_logger.addHandler(ch)

    return log_file


# ============================================================================
# CLI entry point
# ============================================================================
def main():
    parser = argparse.ArgumentParser(
        description="Solar Directory Pipeline Orchestrator",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python pipeline_orchestrator.py --mode weekly
  python pipeline_orchestrator.py --mode backfill --regions CA,TX,FL
  python pipeline_orchestrator.py --mode monthly --dry-run --verbose
        """,
    )
    parser.add_argument(
        "--mode",
        required=True,
        choices=["backfill", "weekly", "monthly"],
        help="Pipeline mode: backfill (all states), weekly (scheduled rotation), monthly (freshness check).",
    )
    parser.add_argument(
        "--regions",
        help="Comma-separated state codes for backfill (e.g., CA,TX,FL). Ignored for weekly/monthly.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Simulate the pipeline without writing to the database or running PHP imports.",
    )
    parser.add_argument(
        "--verbose", "-v",
        action="store_true",
        help="Enable DEBUG-level console output.",
    )

    args = parser.parse_args()

    # ------------------------------------------------------------------
    # Initialize configuration
    # ------------------------------------------------------------------
    config = PipelineConfig()

    # Setup logging
    log_file = setup_logging(config.log_dir, verbose=args.verbose)

    logger = logging.getLogger("pipeline_orchestrator")
    logger.info("=" * 70)
    logger.info("Solar Directory Pipeline Orchestrator")
    logger.info("Mode: %s | Dry-run: %s | Verbose: %s", args.mode, args.dry_run, args.verbose)
    logger.info("Started: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("Log file: %s", log_file)
    logger.info("Flynax root: %s", config.flynax_root)
    logger.info("Data dir: %s", config.data_dir)
    logger.info("=" * 70)

    # ------------------------------------------------------------------
    # Initialize database bridge
    # ------------------------------------------------------------------
    db = None
    try:
        db = DatabaseBridge(config)
        logger.info("Database connection established.")
    except Exception as exc:
        logger.error("Could not connect to database: %s", exc)
        if not args.dry_run:
            logger.error("Database is required for non-dry-run mode. Exiting.")
            sys.exit(1)
        else:
            logger.warning("Continuing in dry-run mode without database.")

    # ------------------------------------------------------------------
    # Initialize Outscraper client
    # ------------------------------------------------------------------
    client = None
    if config.outscraper_api_key:
        try:
            client = SolarOutscraperClient(
                api_key=config.outscraper_api_key,
                monthly_budget=config.monthly_credit_budget,
            )
            logger.info("Outscraper client initialized (budget: %d credits/month).", config.monthly_credit_budget)
        except Exception as exc:
            logger.error("Could not initialize Outscraper client: %s", exc)
    else:
        logger.warning("OUTSCRAPER_API_KEY not set. Scraping will not be available.")

    # ------------------------------------------------------------------
    # Run pipeline
    # ------------------------------------------------------------------
    pipeline = Pipeline(config, db, client)
    pipeline.dry_run = args.dry_run

    exit_code = 0
    start_time = time.time()

    try:
        if args.mode == "backfill":
            regions = [r.strip() for r in args.regions.split(",")] if args.regions else None
            exit_code = pipeline.run_backfill(regions)

        elif args.mode == "weekly":
            exit_code = pipeline.run_weekly()

        elif args.mode == "monthly":
            exit_code = pipeline.run_monthly()

    except KeyboardInterrupt:
        logger.warning("Pipeline interrupted by user.")
        exit_code = 130
    except Exception as exc:
        logger.error("Unhandled exception in pipeline: %s", exc, exc_info=True)
        exit_code = 1
    finally:
        elapsed = time.time() - start_time
        logger.info("Pipeline finished in %.1f seconds (exit code: %d).", elapsed, exit_code)

        # Cleanup
        if db:
            db.close()

    sys.exit(exit_code)


# ============================================================================
if __name__ == "__main__":
    main()
