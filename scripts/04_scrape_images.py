#!/usr/bin/env python3
"""
04_scrape_images.py
===================
Downloads business images from solar installer websites.

Usage:
    python scripts/04_scrape_images.py \
        --input data/enriched_solar_installers.csv \
        --output-dir data/images \
        --max-per-business 3
"""

import argparse
import asyncio
import logging
import os
import re
import sys
from datetime import datetime
from pathlib import Path
from urllib.parse import urljoin, urlparse

import aiohttp
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
# Constants
# ---------------------------------------------------------------------------
# Patterns to exclude from image URLs
EXCLUDE_PATTERNS = [
    r"logo", r"favicon", r"icon", r"sprite", r"avatar",
    r"pixel", r"tracking", r"badge", r"button", r"arrow",
    r"spacer", r"blank", r"transparent", r"1x1",
    r"facebook", r"twitter", r"instagram", r"linkedin",
    r"youtube", r"google", r"yelp", r"pinterest",
    r"\.svg$", r"\.gif$", r"data:image",
]

# Valid image extensions
VALID_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp"}

# Request settings
REQUEST_TIMEOUT = aiohttp.ClientTimeout(total=15)
MAX_IMAGE_SIZE = 10 * 1024 * 1024  # 10 MB max per image
USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/120.0.0.0 Safari/537.36"
)


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def is_valid_image_url(url: str) -> bool:
    """Check if a URL is likely a qualifying image (not a logo/icon/tracker)."""
    url_lower = url.lower()
    for pattern in EXCLUDE_PATTERNS:
        if re.search(pattern, url_lower):
            return False
    # Check extension
    parsed = urlparse(url_lower)
    path = parsed.path
    ext = os.path.splitext(path)[1]
    if ext and ext not in VALID_EXTENSIONS:
        return False
    return True


def extract_image_dimensions(tag_str: str) -> tuple:
    """Extract width and height from an img tag string. Returns (width, height) or (0, 0)."""
    width = 0
    height = 0
    w_match = re.search(r'width[=:]\s*["\']?(\d+)', tag_str, re.IGNORECASE)
    h_match = re.search(r'height[=:]\s*["\']?(\d+)', tag_str, re.IGNORECASE)
    if w_match:
        width = int(w_match.group(1))
    if h_match:
        height = int(h_match.group(1))
    return (width, height)


def parse_images_from_html(html: str, base_url: str) -> list:
    """Extract qualifying image URLs from HTML content."""
    if not html:
        return []

    # Find all img tags
    img_tags = re.findall(r'<img[^>]+>', html, re.IGNORECASE)

    candidates = []
    for tag in img_tags:
        # Extract src
        src_match = re.search(r'src=["\']([^"\']+)["\']', tag, re.IGNORECASE)
        if not src_match:
            # Try data-src (lazy loading)
            src_match = re.search(r'data-src=["\']([^"\']+)["\']', tag, re.IGNORECASE)
        if not src_match:
            continue

        src = src_match.group(1).strip()
        if not src or src.startswith("data:"):
            continue

        # Make absolute URL
        abs_url = urljoin(base_url, src)

        # Check if valid
        if not is_valid_image_url(abs_url):
            continue

        # Check dimensions (if available in tag)
        width, height = extract_image_dimensions(tag)
        if (width > 0 and width < 200) or (height > 0 and height < 200):
            continue

        # Prioritize larger images
        size_score = max(width, 0) * max(height, 0) if width > 0 and height > 0 else 0
        candidates.append((abs_url, size_score))

    # Sort by size score descending (larger images first), then deduplicate
    candidates.sort(key=lambda x: x[1], reverse=True)

    seen = set()
    unique = []
    for url, score in candidates:
        if url not in seen:
            seen.add(url)
            unique.append(url)

    return unique


# ---------------------------------------------------------------------------
# Async fetch functions
# ---------------------------------------------------------------------------
async def fetch_html(session: aiohttp.ClientSession, url: str) -> str:
    """Fetch HTML content from a URL."""
    try:
        headers = {"User-Agent": USER_AGENT}
        async with session.get(url, headers=headers, timeout=REQUEST_TIMEOUT, ssl=False) as resp:
            if resp.status != 200:
                return ""
            content_type = resp.headers.get("Content-Type", "")
            if "text/html" not in content_type and "text" not in content_type:
                return ""
            return await resp.text(errors="replace")
    except Exception:
        return ""


async def download_image(
    session: aiohttp.ClientSession,
    url: str,
    save_path: str,
    max_retries: int = 2,
) -> bool:
    """Download a single image with retry logic."""
    for attempt in range(max_retries + 1):
        try:
            headers = {"User-Agent": USER_AGENT}
            async with session.get(url, headers=headers, timeout=REQUEST_TIMEOUT, ssl=False) as resp:
                if resp.status != 200:
                    return False

                content_type = resp.headers.get("Content-Type", "")
                if "image" not in content_type:
                    return False

                content_length = resp.headers.get("Content-Length")
                if content_length and int(content_length) > MAX_IMAGE_SIZE:
                    return False

                # Read image data
                data = await resp.read()
                if len(data) < 1000:  # Skip tiny files (likely tracking pixels)
                    return False
                if len(data) > MAX_IMAGE_SIZE:
                    return False

                # Determine extension from content type
                ext = ".jpg"
                if "png" in content_type:
                    ext = ".png"
                elif "webp" in content_type:
                    ext = ".webp"
                elif "jpeg" in content_type or "jpg" in content_type:
                    ext = ".jpg"

                # Ensure save path has correct extension
                base, _ = os.path.splitext(save_path)
                final_path = base + ext

                os.makedirs(os.path.dirname(final_path), exist_ok=True)
                with open(final_path, "wb") as f:
                    f.write(data)

                return True

        except asyncio.TimeoutError:
            if attempt < max_retries:
                await asyncio.sleep(1)
                continue
            return False
        except Exception:
            if attempt < max_retries:
                await asyncio.sleep(1)
                continue
            return False

    return False


# ---------------------------------------------------------------------------
# Process a single business
# ---------------------------------------------------------------------------
async def process_business(
    session: aiohttp.ClientSession,
    row_index: int,
    website: str,
    output_dir: str,
    max_images: int,
) -> dict:
    """Scrape and download images for a single business."""
    result = {
        "row_index": row_index,
        "image_1_path": "",
        "image_2_path": "",
        "image_3_path": "",
    }

    if not website or not str(website).startswith("http"):
        return result

    # Fetch homepage HTML
    html = await fetch_html(session, website)
    if not html:
        return result

    # Extract image URLs
    image_urls = parse_images_from_html(html, website)
    if not image_urls:
        return result

    # Download top N images
    business_dir = os.path.join(output_dir, str(row_index))
    downloaded = 0

    for img_url in image_urls[:max_images * 2]:  # Try extra URLs in case of failures
        if downloaded >= max_images:
            break

        photo_num = downloaded + 1
        save_path = os.path.join(business_dir, f"photo_{photo_num}.jpg")

        success = await download_image(session, img_url, save_path)
        if success:
            # Find the actual saved file (extension might have changed)
            base = os.path.join(business_dir, f"photo_{photo_num}")
            for ext in [".jpg", ".png", ".webp"]:
                candidate = base + ext
                if os.path.exists(candidate):
                    result[f"image_{photo_num}_path"] = candidate
                    break
            downloaded += 1

    return result


# ---------------------------------------------------------------------------
# Main async pipeline
# ---------------------------------------------------------------------------
async def scrape_images(
    input_path: str,
    output_dir: str,
    max_per_business: int,
    concurrency: int,
) -> None:
    logger.info("Loading CSV: %s", input_path)
    try:
        df = pd.read_csv(input_path, low_memory=False)
    except FileNotFoundError:
        logger.error("Input file not found: %s", input_path)
        sys.exit(1)

    total_records = len(df)
    logger.info("Loaded %d records", total_records)

    # Determine website column
    website_col = None
    for col in ["website", "website_clean", "site"]:
        if col in df.columns:
            website_col = col
            break

    if not website_col:
        logger.error("No website column found in CSV.")
        sys.exit(1)

    # Filter to records with websites
    has_website = df[website_col].astype(str).str.startswith("http")
    df_with_sites = df[has_website]
    logger.info("Records with website: %d", len(df_with_sites))

    # Create output directory
    os.makedirs(output_dir, exist_ok=True)

    # Process in batches with a semaphore for concurrency control
    semaphore = asyncio.Semaphore(concurrency)
    all_results = []

    async def process_with_semaphore(session, idx, website, output_dir, max_images):
        async with semaphore:
            result = await process_business(session, idx, website, output_dir, max_images)
            # Small delay for politeness
            await asyncio.sleep(0.3)
            return result

    connector = aiohttp.TCPConnector(limit=concurrency, force_close=True)
    async with aiohttp.ClientSession(connector=connector) as session:
        tasks = []
        for idx, row in df_with_sites.iterrows():
            website = str(row[website_col]).strip()
            tasks.append(process_with_semaphore(session, idx, website, output_dir, max_per_business))

        # Process with progress bar
        logger.info("Downloading images (max %d per business, concurrency=%d) ...",
                     max_per_business, concurrency)

        progress = tqdm(total=len(tasks), desc="Scraping images", unit="site")

        for coro in asyncio.as_completed(tasks):
            result = await coro
            all_results.append(result)
            progress.update(1)

        progress.close()

    # Build mapping dataframe
    mapping_df = pd.DataFrame(all_results)

    # Sort by row_index for consistency
    if not mapping_df.empty:
        mapping_df = mapping_df.sort_values("row_index").reset_index(drop=True)

    # Statistics
    total_with_images = mapping_df[
        (mapping_df["image_1_path"] != "") |
        (mapping_df["image_2_path"] != "") |
        (mapping_df["image_3_path"] != "")
    ].shape[0] if not mapping_df.empty else 0

    total_images = sum(
        (mapping_df[f"image_{i}_path"] != "").sum()
        for i in range(1, 4)
    ) if not mapping_df.empty else 0

    logger.info("=" * 60)
    logger.info("IMAGE SCRAPING STATISTICS")
    logger.info("=" * 60)
    logger.info("  Total records:             %d", total_records)
    logger.info("  Records with websites:     %d", len(df_with_sites))
    logger.info("  Businesses with images:    %d", total_with_images)
    logger.info("  Total images downloaded:   %d", total_images)
    if total_with_images > 0:
        logger.info("  Avg images per business:   %.1f", total_images / total_with_images)
    logger.info("=" * 60)

    # Save mapping CSV
    mapping_path = os.path.join(os.path.dirname(output_dir), "image_mapping.csv")
    os.makedirs(os.path.dirname(mapping_path) or ".", exist_ok=True)
    mapping_df.to_csv(mapping_path, index=False)
    logger.info("Saved image mapping to: %s", mapping_path)

    # Also save to output_dir parent for convenience
    alt_mapping = os.path.join(output_dir, "..", "image_mapping.csv")
    try:
        alt_path = os.path.normpath(alt_mapping)
        if alt_path != os.path.normpath(mapping_path):
            mapping_df.to_csv(alt_path, index=False)
    except Exception:
        pass


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def main():
    parser = argparse.ArgumentParser(
        description="Download business images from solar installer websites.",
    )
    parser.add_argument(
        "--input", "-i",
        required=True,
        help="Path to enriched CSV file.",
    )
    parser.add_argument(
        "--output-dir", "-d",
        required=True,
        help="Directory to save downloaded images.",
    )
    parser.add_argument(
        "--max-per-business", "-m",
        type=int,
        default=3,
        help="Maximum images to download per business (default: 3).",
    )
    parser.add_argument(
        "--concurrency", "-c",
        type=int,
        default=10,
        help="Maximum concurrent HTTP connections (default: 10).",
    )
    args = parser.parse_args()

    logger.info("=" * 60)
    logger.info("Solar Installer Image Scraper")
    logger.info("Started: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("=" * 60)

    asyncio.run(scrape_images(
        args.input,
        args.output_dir,
        args.max_per_business,
        args.concurrency,
    ))

    logger.info("Done.")


if __name__ == "__main__":
    main()
