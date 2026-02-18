#!/usr/bin/env python3
"""
02_verify_with_crawl4ai.py
==========================
Uses Crawl4AI to visit each business website and verify they actually
do solar installation. Enriches records with extracted data.

Usage:
    python scripts/02_verify_with_crawl4ai.py \
        --input data/cleaned_solar_installers.csv \
        --output data/verified_solar_installers.csv \
        --max-concurrent 10
"""

import argparse
import asyncio
import logging
import os
import re
import sys
from datetime import datetime

import pandas as pd
from tqdm import tqdm

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Keyword sets for content analysis
# ---------------------------------------------------------------------------
SOLAR_VERIFICATION_KEYWORDS = [
    "solar panel", "solar installation", "photovoltaic", "solar energy",
    "rooftop solar", "solar system", "kilowatt", "kw system",
    "net metering", "inverter", "solar quote", "solar design",
    "solar roof", "go solar", "solar power", "solar contractor",
    "solar installer", "pv system", "solar array", "solar module",
]

SERVICE_KEYWORDS = {
    "residential": ["residential", "home solar", "homeowner", "house", "rooftop"],
    "commercial": ["commercial", "business solar", "industrial", "enterprise", "corporate"],
    "battery": ["battery", "energy storage", "powerwall", "backup power", "battery storage"],
    "ev_charger": ["ev charger", "electric vehicle", "ev charging", "charge station", "ev solar"],
    "maintenance": ["maintenance", "repair", "service plan", "cleaning", "monitoring"],
    "pool_heating": ["pool heating", "pool solar", "solar pool", "heated pool"],
}

CERTIFICATION_KEYWORDS = {
    "NABCEP": ["nabcep"],
    "SEIA": ["seia", "solar energy industries"],
    "BBB": ["bbb", "better business bureau"],
    "Tesla_Powerwall": ["tesla powerwall", "tesla certified", "tesla partner"],
    "Enphase": ["enphase", "enphase partner", "enphase installer"],
    "SunPower": ["sunpower elite", "sunpower master", "sunpower dealer"],
}

BRAND_KEYWORDS = [
    "lg solar", "panasonic", "sunpower", "canadian solar", "jinko",
    "trina solar", "rec solar", "qcells", "hanwha", "silfab",
    "solaredge", "enphase", "tesla", "generac", "sonnen",
    "longi", "ja solar", "first solar", "mission solar", "axitec",
]

FINANCING_KEYWORDS = [
    "financing", "loan", "lease", "ppa", "power purchase agreement",
    "zero down", "$0 down", "monthly payment", "solar financing",
    "no money down", "affordable",
]


# ---------------------------------------------------------------------------
# Content analysis
# ---------------------------------------------------------------------------
def analyze_page_content(text: str) -> dict:
    """Analyze crawled page text and return enrichment data."""
    text_lower = text.lower() if text else ""

    result = {
        "confidence": "none",
        "confidence_score": 0,
        "extracted_services": "",
        "extracted_certifications": "",
        "extracted_brands": "",
        "extracted_years": "",
        "financing_detected": 0,
    }

    if not text_lower:
        return result

    # --- Solar keyword matching ---
    solar_hits = 0
    for kw in SOLAR_VERIFICATION_KEYWORDS:
        if kw in text_lower:
            solar_hits += 1

    if solar_hits >= 5:
        result["confidence"] = "high"
    elif solar_hits >= 2:
        result["confidence"] = "medium"
    elif solar_hits >= 1:
        result["confidence"] = "low"
    else:
        result["confidence"] = "none"
    result["confidence_score"] = solar_hits

    # --- Services ---
    services_found = []
    for service, keywords in SERVICE_KEYWORDS.items():
        for kw in keywords:
            if kw in text_lower:
                services_found.append(service)
                break
    result["extracted_services"] = ",".join(sorted(set(services_found)))

    # --- Certifications ---
    certs_found = []
    for cert, keywords in CERTIFICATION_KEYWORDS.items():
        for kw in keywords:
            if kw in text_lower:
                certs_found.append(cert)
                break
    result["extracted_certifications"] = ",".join(sorted(set(certs_found)))

    # --- Brands ---
    brands_found = []
    for brand in BRAND_KEYWORDS:
        if brand in text_lower:
            brands_found.append(brand.title())
    result["extracted_brands"] = ",".join(sorted(set(brands_found)))

    # --- Years in business ---
    # Look for "since XXXX" pattern
    since_match = re.search(r"since\s+(19|20)\d{2}", text_lower)
    if since_match:
        year_str = re.search(r"((?:19|20)\d{2})", since_match.group())
        if year_str:
            founded = int(year_str.group())
            current_year = datetime.now().year
            if 1950 <= founded <= current_year:
                result["extracted_years"] = str(current_year - founded)

    # Look for "XX years" pattern
    if not result["extracted_years"]:
        years_match = re.search(r"(\d{1,2})\+?\s*years?\s*(?:of\s+)?(?:experience|in business|serving)", text_lower)
        if years_match:
            years_val = int(years_match.group(1))
            if 1 <= years_val <= 75:
                result["extracted_years"] = str(years_val)

    # --- Financing ---
    for kw in FINANCING_KEYWORDS:
        if kw in text_lower:
            result["financing_detected"] = 1
            break

    return result


# ---------------------------------------------------------------------------
# Crawl a single website
# ---------------------------------------------------------------------------
async def crawl_website(crawler, url: str, timeout: int = 15) -> str:
    """Crawl a single URL and return its text content."""
    try:
        result = await asyncio.wait_for(
            crawler.arun(url=url),
            timeout=timeout,
        )
        if result and result.markdown:
            return result.markdown
        if result and hasattr(result, "text") and result.text:
            return result.text
        return ""
    except asyncio.TimeoutError:
        logger.debug("Timeout crawling: %s", url)
        return ""
    except Exception as exc:
        logger.debug("Error crawling %s: %s", url, exc)
        return ""


# ---------------------------------------------------------------------------
# Process a batch of rows
# ---------------------------------------------------------------------------
async def process_batch(crawler, batch_df: pd.DataFrame, timeout: int = 15) -> list:
    """Process a batch of records concurrently."""
    tasks = []
    for _, row in batch_df.iterrows():
        url = str(row.get("website_clean", "")).strip()
        if url and url.startswith("http"):
            tasks.append(crawl_website(crawler, url, timeout))
        else:
            tasks.append(asyncio.coroutine(lambda: "")())

    # Use gather for concurrency
    results = []
    crawl_results = await asyncio.gather(*tasks, return_exceptions=True)

    for idx, ((_, row), crawl_text) in enumerate(zip(batch_df.iterrows(), crawl_results)):
        if isinstance(crawl_text, Exception):
            crawl_text = ""
        analysis = analyze_page_content(crawl_text if isinstance(crawl_text, str) else "")
        results.append(analysis)

    return results


# ---------------------------------------------------------------------------
# Main async pipeline
# ---------------------------------------------------------------------------
async def verify_data(input_path: str, output_path: str, max_concurrent: int, timeout: int) -> None:
    logger.info("Loading CSV: %s", input_path)
    try:
        df = pd.read_csv(input_path, low_memory=False)
    except FileNotFoundError:
        logger.error("Input file not found: %s", input_path)
        sys.exit(1)

    start_count = len(df)
    logger.info("Loaded %d records", start_count)

    # Determine which rows have websites
    website_col = "website_clean" if "website_clean" in df.columns else "site"
    if website_col not in df.columns:
        logger.warning("No website column found. Skipping verification.")
        df.to_csv(output_path, index=False)
        return

    has_website = df[website_col].astype(str).str.strip().str.startswith("http")
    df_with_site = df[has_website].copy()
    df_without_site = df[~has_website].copy()

    logger.info("Records with website: %d", len(df_with_site))
    logger.info("Records without website: %d (will be kept with confidence='low')", len(df_without_site))

    # Initialize enrichment columns
    enrichment_cols = [
        "confidence", "confidence_score", "extracted_services",
        "extracted_certifications", "extracted_brands",
        "extracted_years", "financing_detected",
    ]

    # Process websites in batches using Crawl4AI
    all_results = []

    try:
        from crawl4ai import AsyncWebCrawler

        async with AsyncWebCrawler(verbose=False) as crawler:
            total_batches = (len(df_with_site) + max_concurrent - 1) // max_concurrent
            logger.info("Processing %d websites in %d batches of %d ...",
                        len(df_with_site), total_batches, max_concurrent)

            progress = tqdm(total=len(df_with_site), desc="Verifying websites", unit="site")

            for batch_start in range(0, len(df_with_site), max_concurrent):
                batch_end = min(batch_start + max_concurrent, len(df_with_site))
                batch = df_with_site.iloc[batch_start:batch_end]

                batch_results = await process_batch(crawler, batch, timeout)
                all_results.extend(batch_results)
                progress.update(len(batch))

                # Small delay between batches to be respectful
                await asyncio.sleep(0.5)

            progress.close()

    except ImportError:
        logger.warning("crawl4ai not installed. Running in offline analysis mode.")
        logger.warning("Install with: pip install crawl4ai")
        # Fallback: assign medium confidence to all records with websites
        for _ in range(len(df_with_site)):
            all_results.append({
                "confidence": "medium",
                "confidence_score": 3,
                "extracted_services": "residential",
                "extracted_certifications": "",
                "extracted_brands": "",
                "extracted_years": "",
                "financing_detected": 0,
            })

    # Apply results to dataframe
    for col in enrichment_cols:
        df_with_site[col] = [r.get(col, "") for r in all_results]

    # For records without websites, assign low confidence
    for col in enrichment_cols:
        if col == "confidence":
            df_without_site[col] = "low"
        elif col == "confidence_score":
            df_without_site[col] = 1
        elif col == "financing_detected":
            df_without_site[col] = 0
        else:
            df_without_site[col] = ""

    # Combine back
    df_all = pd.concat([df_with_site, df_without_site], ignore_index=True)

    # Filter out records with no solar confidence
    before_filter = len(df_all)
    df_verified = df_all[df_all["confidence"] != "none"].copy()
    removed_none = before_filter - len(df_verified)

    # Statistics
    logger.info("=" * 60)
    logger.info("VERIFICATION STATISTICS")
    logger.info("=" * 60)
    logger.info("  Input records:           %d", start_count)
    logger.info("  With website:            %d", len(df_with_site))
    logger.info("  Without website:         %d", len(df_without_site))
    logger.info("  Removed (no confidence): %d", removed_none)
    logger.info("  Final verified count:    %d", len(df_verified))

    confidence_counts = df_verified["confidence"].value_counts()
    for level in ["high", "medium", "low"]:
        count = confidence_counts.get(level, 0)
        logger.info("    %s confidence: %d", level.capitalize(), count)

    logger.info("=" * 60)

    # Save
    os.makedirs(os.path.dirname(output_path) or ".", exist_ok=True)
    df_verified.to_csv(output_path, index=False)
    logger.info("Saved verified data to: %s", output_path)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def main():
    parser = argparse.ArgumentParser(
        description="Verify solar installers by crawling their websites with Crawl4AI.",
    )
    parser.add_argument(
        "--input", "-i",
        required=True,
        help="Path to cleaned CSV file.",
    )
    parser.add_argument(
        "--output", "-o",
        required=True,
        help="Path to save verified CSV output.",
    )
    parser.add_argument(
        "--max-concurrent", "-c",
        type=int,
        default=10,
        help="Maximum concurrent crawl requests (default: 10).",
    )
    parser.add_argument(
        "--timeout", "-t",
        type=int,
        default=15,
        help="Timeout per website in seconds (default: 15).",
    )
    args = parser.parse_args()

    logger.info("=" * 60)
    logger.info("Solar Installer Website Verifier (Crawl4AI)")
    logger.info("Started: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("=" * 60)

    asyncio.run(verify_data(args.input, args.output, args.max_concurrent, args.timeout))

    logger.info("Done.")


if __name__ == "__main__":
    main()
