"""
outscraper_client.py
====================
Wrapper around the Outscraper Python SDK for solar installer scraping.
Handles retry logic, credit tracking, and budget enforcement.
"""

import logging
import time

logger = logging.getLogger(__name__)

# Fields we request from Outscraper
SCRAPE_FIELDS = [
    "name", "full_address", "city", "state", "phone", "site",
    "rating", "reviews", "business_status", "place_id",
    "latitude", "longitude", "type", "subtypes", "country",
]

# Search queries for solar installers
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
        """Lazy-initialize the Outscraper client."""
        if self._client is None:
            try:
                from outscraper import ApiClient
                self._client = ApiClient(api_key=self.api_key)
            except ImportError:
                raise ImportError(
                    "outscraper package not installed. Run: pip install outscraper"
                )
        return self._client

    def scrape_region(self, state_name: str, queries: list = None,
                       limit_per_query: int = 500, current_monthly_usage: int = 0) -> list:
        """
        Scrape solar installers in a US state via Google Maps.

        Args:
            state_name: Full state name (e.g., "California")
            queries: Search queries (defaults to solar-related queries)
            limit_per_query: Max results per query
            current_monthly_usage: Credits already used this month

        Returns:
            List of business dicts from Outscraper
        """
        if queries is None:
            queries = DEFAULT_QUERIES

        # Budget check
        estimated_cost = len(queries) * limit_per_query
        if current_monthly_usage + self._credits_used + estimated_cost > self.monthly_budget:
            logger.warning(
                "Budget limit approaching. Used: %d, estimated: %d, budget: %d",
                current_monthly_usage + self._credits_used, estimated_cost, self.monthly_budget,
            )
            # Still proceed but with reduced limits
            limit_per_query = min(limit_per_query, 100)

        client = self._get_client()
        all_results = []
        seen_place_ids = set()

        for query in queries:
            full_query = f"{query} in {state_name}"
            logger.info("Scraping: '%s' (limit=%d)", full_query, limit_per_query)

            retries = 0
            max_retries = 3

            while retries < max_retries:
                try:
                    results = client.google_maps_search(
                        full_query,
                        limit=limit_per_query,
                        language="en",
                        region="US",
                    )

                    # Outscraper returns list of lists
                    if results and isinstance(results, list):
                        for batch in results:
                            if isinstance(batch, list):
                                for record in batch:
                                    if isinstance(record, dict):
                                        pid = record.get("place_id", "")
                                        if pid and pid not in seen_place_ids:
                                            seen_place_ids.add(pid)
                                            all_results.append(record)
                            elif isinstance(batch, dict):
                                pid = batch.get("place_id", "")
                                if pid and pid not in seen_place_ids:
                                    seen_place_ids.add(pid)
                                    all_results.append(batch)

                    self._credits_used += limit_per_query
                    logger.info(
                        "  Got %d unique results so far for %s",
                        len(all_results), state_name,
                    )
                    break  # Success

                except Exception as exc:
                    retries += 1
                    wait = 2 ** retries
                    logger.warning(
                        "  Outscraper error (attempt %d/%d): %s. Retrying in %ds...",
                        retries, max_retries, exc, wait,
                    )
                    time.sleep(wait)

            # Rate limit between queries
            time.sleep(1)

        logger.info(
            "Scrape complete for %s: %d unique businesses, ~%d credits used",
            state_name, len(all_results), self._credits_used,
        )
        return all_results

    def enrich_by_place_ids(self, place_ids: list) -> list:
        """
        Re-fetch data for existing businesses by their Google Place IDs.
        Used for monthly verification/updates.

        Args:
            place_ids: List of Google Place ID strings (max 20 per call)

        Returns:
            List of updated business dicts
        """
        if not place_ids:
            return []

        client = self._get_client()
        results = []

        # Process in batches of 20
        for i in range(0, len(place_ids), 20):
            batch = place_ids[i:i + 20]
            logger.info("Enriching batch of %d place IDs (%d/%d)", len(batch), i, len(place_ids))

            retries = 0
            max_retries = 3

            while retries < max_retries:
                try:
                    batch_results = client.google_maps_search(
                        batch,
                        language="en",
                        region="US",
                    )

                    if batch_results and isinstance(batch_results, list):
                        for item in batch_results:
                            if isinstance(item, list):
                                results.extend(
                                    r for r in item if isinstance(r, dict)
                                )
                            elif isinstance(item, dict):
                                results.append(item)

                    self._credits_used += len(batch)
                    break

                except Exception as exc:
                    retries += 1
                    wait = 2 ** retries
                    logger.warning(
                        "  Enrichment error (attempt %d/%d): %s. Retrying in %ds...",
                        retries, max_retries, exc, wait,
                    )
                    time.sleep(wait)

            time.sleep(0.5)

        logger.info("Enrichment complete: %d records updated", len(results))
        return results

    def get_credits_used(self) -> int:
        """Return credits used in this session."""
        return self._credits_used
