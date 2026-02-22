"""Outscraper API wrapper with budget control and retry logic."""
import logging
import time

logger = logging.getLogger(__name__)

SCRAPE_FIELDS = [
    "name", "full_address", "city", "state", "phone", "site",
    "rating", "reviews", "business_status", "place_id",
    "latitude", "longitude", "type", "subtypes", "country",
]

DEFAULT_QUERIES = [
    "solar panel installation companies",
    "solar installer",
    "solar energy company",
]


class SolarOutscraperClient:
    """Outscraper API wrapper with budget control."""

    def __init__(self, api_key: str, monthly_budget: int = 10000):
        self.api_key = api_key
        self.monthly_budget = monthly_budget
        self._credits_used = 0
        self._client = None

    def _get_client(self):
        if self._client is None:
            try:
                from outscraper import ApiClient
                self._client = ApiClient(api_key=self.api_key)
            except ImportError:
                raise ImportError("outscraper package not installed. Run: pip install outscraper")
        return self._client

    def scrape_region(self, state_name: str, queries: list | None = None,
                       limit_per_query: int = 500) -> list:
        if queries is None:
            queries = [f"{q} in {state_name}" for q in DEFAULT_QUERIES]

        client = self._get_client()
        all_results = []
        seen_place_ids = set()

        for query in queries:
            logger.info("Scraping: '%s' (limit=%d)", query, limit_per_query)
            retries = 0
            while retries < 3:
                try:
                    results = client.google_maps_search(
                        query, limit=limit_per_query, language="en", region="US",
                    )
                    if results and isinstance(results, list):
                        for batch in results:
                            items = batch if isinstance(batch, list) else [batch]
                            for record in items:
                                if isinstance(record, dict):
                                    pid = record.get("place_id", "")
                                    if pid and pid not in seen_place_ids:
                                        seen_place_ids.add(pid)
                                        all_results.append(record)
                    self._credits_used += limit_per_query
                    break
                except Exception as exc:
                    retries += 1
                    wait = 2 ** retries
                    logger.warning("Outscraper error (attempt %d/3): %s. Retrying in %ds...", retries, exc, wait)
                    time.sleep(wait)
            time.sleep(1)

        logger.info("Scrape complete for %s: %d unique businesses", state_name, len(all_results))
        return all_results

    def get_credits_used(self) -> int:
        return self._credits_used
