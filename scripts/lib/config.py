"""
config.py
=========
Configuration loader for the Solar Directory pipeline.
Reads .env file and parses Flynax config.inc.php for DB credentials.
"""

import os
import re
from pathlib import Path


def _parse_flynax_config(config_path: Path) -> dict:
    """Extract RL_DB* constants from Flynax's config.inc.php."""
    result = {}
    if not config_path.exists():
        return result

    content = config_path.read_text(encoding="utf-8", errors="ignore")

    # Match: define('RL_DBHOST', 'localhost');
    for match in re.finditer(r"define\(\s*'(RL_\w+)'\s*,\s*'([^']*)'\s*\)", content):
        result[match.group(1)] = match.group(2)

    # Match: define('RL_DBPORT', 3306);
    for match in re.finditer(r"define\(\s*'(RL_\w+)'\s*,\s*(\d+)\s*\)", content):
        result[match.group(1)] = match.group(2)

    return result


class PipelineConfig:
    """Central configuration for the pipeline."""

    def __init__(self, env_path: str = None):
        # Determine paths
        self.scripts_dir = Path(__file__).resolve().parent.parent
        self.flynax_root = self.scripts_dir.parent

        # Load .env
        env_file = Path(env_path) if env_path else self.scripts_dir / ".env"
        if env_file.exists():
            try:
                from dotenv import load_dotenv
                load_dotenv(env_file)
            except ImportError:
                # Manual .env parsing fallback
                for line in env_file.read_text().splitlines():
                    line = line.strip()
                    if line and not line.startswith("#") and "=" in line:
                        key, _, value = line.partition("=")
                        os.environ.setdefault(key.strip(), value.strip())

        # Parse Flynax config for DB credentials
        flynax_cfg = _parse_flynax_config(
            self.flynax_root / "includes" / "config.inc.php"
        )

        # Database settings (env overrides Flynax config)
        self.db_host = os.getenv("DB_HOST", flynax_cfg.get("RL_DBHOST", "localhost"))
        self.db_port = int(os.getenv("DB_PORT", flynax_cfg.get("RL_DBPORT", "3306")))
        self.db_user = os.getenv("DB_USER", flynax_cfg.get("RL_DBUSER", ""))
        self.db_pass = os.getenv("DB_PASS", flynax_cfg.get("RL_DBPASS", ""))
        self.db_name = os.getenv("DB_NAME", flynax_cfg.get("RL_DBNAME", ""))
        self.db_prefix = os.getenv("DB_PREFIX", flynax_cfg.get("RL_DBPREFIX", "fl_"))

        # Outscraper
        self.outscraper_api_key = os.getenv("OUTSCRAPER_API_KEY", "")
        self.monthly_credit_budget = int(os.getenv("MONTHLY_CREDIT_BUDGET", "10000"))

        # Pipeline settings
        self.weekly_region_count = int(os.getenv("WEEKLY_REGION_COUNT", "5"))
        self.crawl4ai_max_concurrent = int(os.getenv("CRAWL4AI_MAX_CONCURRENT", "10"))
        self.crawl4ai_timeout = int(os.getenv("CRAWL4AI_TIMEOUT", "15"))

        # Notification
        self.admin_email = os.getenv("ADMIN_EMAIL", "")

        # Paths
        self.data_dir = Path(os.getenv("DATA_DIR", str(self.scripts_dir / "data")))
        self.log_dir = Path(os.getenv("LOG_DIR", str(self.scripts_dir / "logs")))

        # Ensure dirs exist
        self.data_dir.mkdir(parents=True, exist_ok=True)
        self.log_dir.mkdir(parents=True, exist_ok=True)

    def validate(self) -> list:
        """Return list of missing required config values."""
        issues = []
        if not self.db_user:
            issues.append("Database user not configured (DB_USER or Flynax config)")
        if not self.db_name:
            issues.append("Database name not configured (DB_NAME or Flynax config)")
        if not self.outscraper_api_key:
            issues.append("OUTSCRAPER_API_KEY not set in .env")
        return issues
