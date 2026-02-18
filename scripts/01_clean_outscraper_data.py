#!/usr/bin/env python3
"""
01_clean_outscraper_data.py
===========================
Cleans raw Outscraper Google Maps CSV data for solar installers.

Usage:
    python scripts/01_clean_outscraper_data.py \
        --input data/raw_outscraper.csv \
        --output data/cleaned_solar_installers.csv
"""

import argparse
import logging
import os
import re
import sys
from datetime import datetime

import pandas as pd
import phonenumbers
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
SOLAR_KEYWORDS = [
    "solar",
    "photovoltaic",
    " pv ",
    "renewable energy",
    "sun power",
    "sunpower",
    "green energy",
    "clean energy",
]

# US state abbreviation -> full name mapping
STATE_ABBR_TO_FULL = {
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

STATE_FULL_TO_ABBR = {v.upper(): k for k, v in STATE_ABBR_TO_FULL.items()}


# ---------------------------------------------------------------------------
# Helper functions
# ---------------------------------------------------------------------------
def normalize_phone(raw_phone: str) -> str:
    """Normalize a phone number to E.164 format using the phonenumbers library."""
    if pd.isna(raw_phone) or not str(raw_phone).strip():
        return ""
    raw = str(raw_phone).strip()
    try:
        parsed = phonenumbers.parse(raw, "US")
        if phonenumbers.is_valid_number(parsed):
            return phonenumbers.format_number(parsed, phonenumbers.PhoneNumberFormat.E164)
    except phonenumbers.NumberParseException:
        pass
    # Fallback: strip non-digits
    digits = re.sub(r"\D", "", raw)
    if len(digits) == 10:
        return f"+1{digits}"
    if len(digits) == 11 and digits.startswith("1"):
        return f"+{digits}"
    return ""


def format_phone_display(e164: str) -> str:
    """Format an E.164 phone number as (XXX) XXX-XXXX."""
    if not e164:
        return ""
    try:
        parsed = phonenumbers.parse(e164, "US")
        return phonenumbers.format_number(parsed, phonenumbers.PhoneNumberFormat.NATIONAL)
    except phonenumbers.NumberParseException:
        return e164


def clean_website(url: str) -> str:
    """Ensure https:// prefix, strip trailing slashes."""
    if pd.isna(url) or not str(url).strip():
        return ""
    url = str(url).strip()
    if not url.startswith(("http://", "https://")):
        url = "https://" + url
    url = url.rstrip("/")
    return url


def clean_business_name(name: str) -> str:
    """Strip extra whitespace and title-case."""
    if pd.isna(name) or not str(name).strip():
        return ""
    cleaned = re.sub(r"\s+", " ", str(name).strip())
    return cleaned.title()


def normalize_address(address: str) -> str:
    """Lowercase + strip punctuation for dedup matching."""
    if pd.isna(address):
        return ""
    return re.sub(r"[^a-z0-9 ]", "", str(address).lower()).strip()


def resolve_state(raw_state: str) -> tuple:
    """Return (state_full, state_abbr) from a raw state value."""
    if pd.isna(raw_state) or not str(raw_state).strip():
        return ("", "")
    s = str(raw_state).strip()
    upper = s.upper()
    # Check if it's an abbreviation
    if upper in STATE_ABBR_TO_FULL:
        return (STATE_ABBR_TO_FULL[upper], upper)
    # Check if it's a full name
    if upper in STATE_FULL_TO_ABBR:
        return (s.title(), STATE_FULL_TO_ABBR[upper])
    # Try partial match
    for full_upper, abbr in STATE_FULL_TO_ABBR.items():
        if full_upper.startswith(upper) or upper.startswith(full_upper):
            return (STATE_ABBR_TO_FULL[abbr], abbr)
    return (s.title(), s.upper()[:2])


def is_solar_related(row: pd.Series) -> bool:
    """Check whether a row is solar-related based on name and type/category."""
    searchable = ""
    for col in ["name", "type", "category", "types", "subtypes"]:
        if col in row.index and pd.notna(row[col]):
            searchable += " " + str(row[col]).lower()
    for kw in SOLAR_KEYWORDS:
        if kw in searchable:
            return True
    return False


# ---------------------------------------------------------------------------
# In-memory pipeline (for use by orchestrator)
# ---------------------------------------------------------------------------
def clean_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    """Clean a DataFrame in-memory without file I/O. Used by pipeline_orchestrator."""
    start_count = len(df)
    if start_count == 0:
        return df

    # Step 1: Remove junk
    df = df.dropna(subset=["name"])
    df = df[df["name"].astype(str).str.strip() != ""]
    if "full_address" in df.columns:
        df = df.dropna(subset=["full_address"])
        df = df[df["full_address"].astype(str).str.strip() != ""]
    if "phone" in df.columns:
        df = df.dropna(subset=["phone"])
        df = df[df["phone"].astype(str).str.strip() != ""]
    if "business_status" in df.columns:
        closed_mask = df["business_status"].astype(str).str.upper().isin(
            ["CLOSED_PERMANENTLY", "CLOSED_TEMPORARILY"]
        )
        df = df[~closed_mask]
    if "country" in df.columns:
        us_mask = df["country"].astype(str).str.strip().str.upper().isin(["US", "UNITED STATES"])
        df = df[us_mask]

    # Step 2: Solar filter
    solar_mask = df.apply(is_solar_related, axis=1)
    df = df[solar_mask].copy()

    # Step 3: Deduplicate
    df["phone_e164"] = df["phone"].astype(str).apply(normalize_phone)
    reviews_col = "reviews" if "reviews" in df.columns else "reviews_count"
    if reviews_col not in df.columns:
        df[reviews_col] = 0
    df[reviews_col] = pd.to_numeric(df[reviews_col], errors="coerce").fillna(0).astype(int)
    if df[df["phone_e164"] != ""].duplicated(subset=["phone_e164"], keep=False).any():
        df = df.sort_values(reviews_col, ascending=False)
        df = df.drop_duplicates(subset=["phone_e164"], keep="first")
    if all(c in df.columns for c in ["full_address", "city", "state"]):
        df["_norm_addr"] = df["full_address"].apply(normalize_address)
        df["_norm_city"] = df["city"].astype(str).str.lower().str.strip()
        df["_norm_state"] = df["state"].astype(str).str.lower().str.strip()
        df = df.sort_values(reviews_col, ascending=False)
        df = df.drop_duplicates(subset=["_norm_addr", "_norm_city", "_norm_state"], keep="first")
        df = df.drop(columns=["_norm_addr", "_norm_city", "_norm_state"])

    # Step 4: Standardize
    if "state" in df.columns:
        state_info = df["state"].apply(resolve_state)
        df["state_full"] = state_info.apply(lambda x: x[0])
        df["state_abbr"] = state_info.apply(lambda x: x[1])
    else:
        df["state_full"] = ""
        df["state_abbr"] = ""
    df["phone_formatted"] = df["phone_e164"].apply(format_phone_display)
    site_col = "site" if "site" in df.columns else "website"
    if site_col in df.columns:
        df["website_clean"] = df[site_col].apply(clean_website)
    else:
        df["website_clean"] = ""
    df["clean_name"] = df["name"].apply(clean_business_name)

    logger.info("clean_dataframe: %d -> %d records", start_count, len(df))
    return df


# ---------------------------------------------------------------------------
# Main pipeline (file-based)
# ---------------------------------------------------------------------------
def clean_data(input_path: str, output_path: str) -> None:
    logger.info("Loading CSV: %s", input_path)
    try:
        df = pd.read_csv(input_path, low_memory=False)
    except FileNotFoundError:
        logger.error("Input file not found: %s", input_path)
        sys.exit(1)
    except Exception as exc:
        logger.error("Failed to read CSV: %s", exc)
        sys.exit(1)

    start_count = len(df)
    logger.info("Starting record count: %d", start_count)

    # -----------------------------------------------------------------------
    # Step 1: Remove junk records
    # -----------------------------------------------------------------------
    logger.info("Step 1 — Removing junk records ...")

    # Drop empty name / address / phone
    df = df.dropna(subset=["name"])
    df = df[df["name"].astype(str).str.strip() != ""]
    after_name = len(df)

    if "full_address" in df.columns:
        df = df.dropna(subset=["full_address"])
        df = df[df["full_address"].astype(str).str.strip() != ""]

    if "phone" in df.columns:
        df = df.dropna(subset=["phone"])
        df = df[df["phone"].astype(str).str.strip() != ""]

    after_required = len(df)
    logger.info("  Removed %d rows with missing name/address/phone", start_count - after_required)

    # Drop closed businesses
    if "business_status" in df.columns:
        closed_mask = df["business_status"].astype(str).str.upper().isin([
            "CLOSED_PERMANENTLY", "CLOSED_TEMPORARILY",
        ])
        removed_closed = closed_mask.sum()
        df = df[~closed_mask]
        logger.info("  Removed %d closed businesses", removed_closed)
    else:
        removed_closed = 0

    # Filter to US only (if country column exists)
    if "country" in df.columns:
        us_mask = df["country"].astype(str).str.strip().str.upper().isin(["US", "UNITED STATES"])
        removed_non_us = (~us_mask).sum()
        df = df[us_mask]
        logger.info("  Removed %d non-US records", removed_non_us)

    after_junk = len(df)

    # -----------------------------------------------------------------------
    # Step 2: Filter to solar-related businesses
    # -----------------------------------------------------------------------
    logger.info("Step 2 — Filtering to solar-related businesses ...")
    tqdm.pandas(desc="Checking solar keywords")
    solar_mask = df.progress_apply(is_solar_related, axis=1)
    removed_non_solar = (~solar_mask).sum()
    df = df[solar_mask].copy()
    logger.info("  Removed %d non-solar businesses (kept %d)", removed_non_solar, len(df))

    # -----------------------------------------------------------------------
    # Step 3: Deduplicate
    # -----------------------------------------------------------------------
    logger.info("Step 3 — Deduplicating ...")

    # Normalize phone numbers
    logger.info("  Normalizing phone numbers ...")
    df["phone_e164"] = df["phone"].astype(str).apply(normalize_phone)

    before_dedup = len(df)

    # Dedup by phone — keep the record with more reviews
    reviews_col = "reviews" if "reviews" in df.columns else "reviews_count"
    if reviews_col not in df.columns:
        # Create dummy column if not present
        df[reviews_col] = 0

    df[reviews_col] = pd.to_numeric(df[reviews_col], errors="coerce").fillna(0).astype(int)

    phone_dupes = df[df["phone_e164"] != ""].duplicated(subset=["phone_e164"], keep=False)
    if phone_dupes.any():
        df = df.sort_values(reviews_col, ascending=False)
        df = df.drop_duplicates(subset=["phone_e164"], keep="first")
    removed_phone_dupes = before_dedup - len(df)
    logger.info("  Removed %d phone duplicates", removed_phone_dupes)

    # Dedup by normalized address + city + state
    before_addr_dedup = len(df)
    if all(c in df.columns for c in ["full_address", "city", "state"]):
        df["_norm_addr"] = df["full_address"].apply(normalize_address)
        df["_norm_city"] = df["city"].astype(str).str.lower().str.strip()
        df["_norm_state"] = df["state"].astype(str).str.lower().str.strip()
        df = df.sort_values(reviews_col, ascending=False)
        df = df.drop_duplicates(subset=["_norm_addr", "_norm_city", "_norm_state"], keep="first")
        df = df.drop(columns=["_norm_addr", "_norm_city", "_norm_state"])
    removed_addr_dupes = before_addr_dedup - len(df)
    logger.info("  Removed %d address duplicates", removed_addr_dupes)

    total_dupes = removed_phone_dupes + removed_addr_dupes
    logger.info("  Total duplicates removed: %d", total_dupes)

    # -----------------------------------------------------------------------
    # Step 4: Standardize data
    # -----------------------------------------------------------------------
    logger.info("Step 4 — Standardizing data ...")

    # State names
    if "state" in df.columns:
        state_info = df["state"].apply(resolve_state)
        df["state_full"] = state_info.apply(lambda x: x[0])
        df["state_abbr"] = state_info.apply(lambda x: x[1])
    else:
        df["state_full"] = ""
        df["state_abbr"] = ""

    # Formatted phone
    df["phone_formatted"] = df["phone_e164"].apply(format_phone_display)

    # Clean websites
    site_col = "site" if "site" in df.columns else "website"
    if site_col in df.columns:
        df["website_clean"] = df[site_col].apply(clean_website)
    else:
        df["website_clean"] = ""

    # Clean business names
    df["clean_name"] = df["name"].apply(clean_business_name)

    # -----------------------------------------------------------------------
    # Step 5: Print statistics
    # -----------------------------------------------------------------------
    final_count = len(df)
    logger.info("=" * 60)
    logger.info("CLEANING STATISTICS")
    logger.info("=" * 60)
    logger.info("  Starting records:        %d", start_count)
    logger.info("  Removed (missing data):  %d", start_count - after_required)
    logger.info("  Removed (closed):        %d", removed_closed)
    logger.info("  Removed (non-solar):     %d", removed_non_solar)
    logger.info("  Removed (duplicates):    %d", total_dupes)
    logger.info("  Final record count:      %d", final_count)
    logger.info("  Retention rate:          %.1f%%", (final_count / start_count * 100) if start_count else 0)
    logger.info("=" * 60)

    # -----------------------------------------------------------------------
    # Step 6: Save output
    # -----------------------------------------------------------------------
    os.makedirs(os.path.dirname(output_path) or ".", exist_ok=True)
    df.to_csv(output_path, index=False)
    logger.info("Saved cleaned data to: %s", output_path)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def main():
    parser = argparse.ArgumentParser(
        description="Clean raw Outscraper Google Maps CSV data for solar installers.",
    )
    parser.add_argument(
        "--input", "-i",
        required=True,
        help="Path to raw Outscraper CSV file.",
    )
    parser.add_argument(
        "--output", "-o",
        required=True,
        help="Path to save cleaned CSV output.",
    )
    args = parser.parse_args()

    logger.info("=" * 60)
    logger.info("Solar Installer Data Cleaner")
    logger.info("Started: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("=" * 60)

    clean_data(args.input, args.output)

    logger.info("Done.")


if __name__ == "__main__":
    main()
