#!/usr/bin/env python3
"""
03_enrich_data.py
=================
Enriches verified solar installer data with geocoding, category assignment,
description generation, and final column mapping for bulk_import.php.

Usage:
    python scripts/03_enrich_data.py \
        --input data/verified_solar_installers.csv \
        --output data/enriched_solar_installers.csv
"""

import argparse
import logging
import os
import re
import sys
import time
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
# Constants — service/brand/cert option ID mappings for bulk_import.php
# ---------------------------------------------------------------------------

# Services offered option IDs
SERVICE_OPTION_IDS = {
    "residential": "1",
    "commercial": "2",
    "battery": "3",
    "ev_charger": "4",
    "maintenance": "5",
    "pool_heating": "6",
}

# Panel brand option IDs
BRAND_OPTION_IDS = {
    "lg solar": "1",
    "panasonic": "2",
    "sunpower": "3",
    "canadian solar": "4",
    "jinko": "5",
    "trina solar": "6",
    "rec solar": "7",
    "qcells": "8",
    "hanwha": "9",
    "silfab": "10",
    "solaredge": "11",
}

# Certification option IDs
CERT_OPTION_IDS = {
    "NABCEP": "1",
    "SEIA": "2",
    "BBB": "3",
    "Tesla_Powerwall": "4",
    "Enphase": "5",
    "SunPower": "6",
}

# Category keys
CATEGORY_KEYS = {
    "residential": "residential_solar",
    "commercial": "commercial_solar",
    "maintenance": "solar_maintenance",
    "battery": "solar_battery_storage",
    "pool_heating": "solar_pool_heating",
    "ev_charger": "ev_charger_solar",
}

# State abbreviations for reverse lookup
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

STATE_FULL_TO_ABBR = {v: k for k, v in STATE_ABBR_TO_FULL.items()}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def slugify(text: str) -> str:
    """Create a URL-friendly slug from text."""
    if pd.isna(text) or not str(text).strip():
        return ""
    slug = str(text).strip().lower()
    slug = re.sub(r"[^a-z0-9\s-]", "", slug)
    slug = re.sub(r"[\s_]+", "-", slug)
    slug = re.sub(r"-+", "-", slug).strip("-")
    return slug


def resolve_state_info(row: pd.Series) -> tuple:
    """Return (state_full, state_abbr) from available columns."""
    # Try state_full first
    if "state_full" in row.index and pd.notna(row["state_full"]) and str(row["state_full"]).strip():
        full = str(row["state_full"]).strip()
        if full in STATE_FULL_TO_ABBR:
            return (full, STATE_FULL_TO_ABBR[full])
        return (full, "")

    # Try state column
    if "state" in row.index and pd.notna(row["state"]) and str(row["state"]).strip():
        s = str(row["state"]).strip()
        upper = s.upper()
        if upper in STATE_ABBR_TO_FULL:
            return (STATE_ABBR_TO_FULL[upper], upper)
        for full_name, abbr in STATE_FULL_TO_ABBR.items():
            if full_name.upper() == upper:
                return (full_name, abbr)
        return (s.title(), s.upper()[:2])

    # Try state_abbr
    if "state_abbr" in row.index and pd.notna(row["state_abbr"]) and str(row["state_abbr"]).strip():
        abbr = str(row["state_abbr"]).strip().upper()
        if abbr in STATE_ABBR_TO_FULL:
            return (STATE_ABBR_TO_FULL[abbr], abbr)

    return ("", "")


def map_services_to_ids(extracted_services: str) -> str:
    """Map extracted service keys to option IDs."""
    if pd.isna(extracted_services) or not str(extracted_services).strip():
        return SERVICE_OPTION_IDS.get("residential", "1")  # Default
    services = str(extracted_services).strip().split(",")
    ids = []
    for svc in services:
        svc = svc.strip().lower()
        if svc in SERVICE_OPTION_IDS:
            ids.append(SERVICE_OPTION_IDS[svc])
    if not ids:
        ids.append(SERVICE_OPTION_IDS.get("residential", "1"))
    return ",".join(sorted(set(ids)))


def map_brands_to_ids(extracted_brands: str) -> str:
    """Map extracted brand names to option IDs."""
    if pd.isna(extracted_brands) or not str(extracted_brands).strip():
        return ""
    brands = str(extracted_brands).strip().split(",")
    ids = []
    for brand in brands:
        brand_lower = brand.strip().lower()
        if brand_lower in BRAND_OPTION_IDS:
            ids.append(BRAND_OPTION_IDS[brand_lower])
    return ",".join(sorted(set(ids)))


def map_certs_to_ids(extracted_certifications: str) -> str:
    """Map extracted certification names to option IDs."""
    if pd.isna(extracted_certifications) or not str(extracted_certifications).strip():
        return ""
    certs = str(extracted_certifications).strip().split(",")
    ids = []
    for cert in certs:
        cert = cert.strip()
        if cert in CERT_OPTION_IDS:
            ids.append(CERT_OPTION_IDS[cert])
    return ",".join(sorted(set(ids)))


def determine_categories(extracted_services: str) -> tuple:
    """Determine primary and additional categories from services."""
    if pd.isna(extracted_services) or not str(extracted_services).strip():
        return ("residential_solar", "")

    services = [s.strip().lower() for s in str(extracted_services).split(",")]

    categories = []
    for svc in services:
        if svc in CATEGORY_KEYS:
            categories.append(CATEGORY_KEYS[svc])

    if not categories:
        return ("residential_solar", "")

    primary = categories[0]
    additional = ",".join(categories[1:]) if len(categories) > 1 else ""
    return (primary, additional)


def generate_description(row: pd.Series) -> str:
    """Generate a template-based description if none exists."""
    # Check if description already exists
    for col in ["description", "about"]:
        if col in row.index and pd.notna(row[col]) and str(row[col]).strip():
            return str(row[col]).strip()

    # Build template description
    name = str(row.get("clean_name", row.get("name", "This company"))).strip()
    city = str(row.get("city", "")).strip()
    state = str(row.get("state_full", row.get("state", ""))).strip()

    # Build location string
    location_parts = []
    if city:
        location_parts.append(city)
    if state:
        location_parts.append(state)
    location = ", ".join(location_parts) if location_parts else "the local area"

    # Services
    services_str = ""
    extracted = str(row.get("extracted_services", "")).strip()
    if extracted:
        service_names = {
            "residential": "residential solar installation",
            "commercial": "commercial solar projects",
            "battery": "battery storage solutions",
            "ev_charger": "EV charger installation",
            "maintenance": "solar system maintenance",
            "pool_heating": "solar pool heating",
        }
        svc_list = [service_names.get(s.strip(), s.strip()) for s in extracted.split(",") if s.strip()]
        if svc_list:
            if len(svc_list) == 1:
                services_str = svc_list[0]
            elif len(svc_list) == 2:
                services_str = f"{svc_list[0]} and {svc_list[1]}"
            else:
                services_str = ", ".join(svc_list[:-1]) + f", and {svc_list[-1]}"
    if not services_str:
        services_str = "residential and commercial solar installation"

    # Years
    years = str(row.get("extracted_years", "")).strip()
    years_part = ""
    if years and years.isdigit() and int(years) > 0:
        years_part = f" With {years} years of experience,"
    else:
        years_part = ""

    # Rating
    rating = ""
    for col in ["rating", "google_rating"]:
        if col in row.index and pd.notna(row[col]):
            try:
                r = float(row[col])
                if 1.0 <= r <= 5.0:
                    rating = f"{r:.1f}"
                    break
            except (ValueError, TypeError):
                pass

    # Reviews
    reviews = ""
    for col in ["reviews", "reviews_count", "total_reviews"]:
        if col in row.index and pd.notna(row[col]):
            try:
                rv = int(float(row[col]))
                if rv > 0:
                    reviews = str(rv)
                    break
            except (ValueError, TypeError):
                pass

    # Build description
    desc = f"{name} is a solar installation company serving {location}."
    desc += f" They specialize in {services_str}."

    if years_part:
        desc += years_part
        if rating:
            desc += f" they maintain a {rating}-star Google rating"
            if reviews:
                desc += f" based on {reviews} reviews"
            desc += "."
        else:
            desc += " they are committed to delivering quality solar solutions."
    elif rating:
        desc += f" They have a {rating}-star Google rating"
        if reviews:
            desc += f" based on {reviews} reviews"
        desc += "."

    return desc


# ---------------------------------------------------------------------------
# Geocoding
# ---------------------------------------------------------------------------
def geocode_missing(df: pd.DataFrame) -> pd.DataFrame:
    """Geocode records that are missing latitude/longitude."""
    lat_col = "latitude" if "latitude" in df.columns else "lat"
    lng_col = "longitude" if "longitude" in df.columns else "lng"

    # Ensure columns exist
    if lat_col not in df.columns:
        df[lat_col] = None
    if lng_col not in df.columns:
        df[lng_col] = None

    # Find records needing geocoding
    needs_geocode = df[
        (df[lat_col].isna() | (df[lat_col] == 0) | (df[lat_col] == ""))
        & df["full_address"].notna()
    ]

    if len(needs_geocode) == 0:
        logger.info("All records already have coordinates. Skipping geocoding.")
        return df

    logger.info("Geocoding %d records missing coordinates ...", len(needs_geocode))

    try:
        from geopy.geocoders import Nominatim
        from geopy.extra.rate_limiter import RateLimiter

        geolocator = Nominatim(
            user_agent="solar_installer_enrichment_v1",
            timeout=10,
        )
        geocode = RateLimiter(geolocator.geocode, min_delay_seconds=1.0)

        success_count = 0
        fail_count = 0

        for idx in tqdm(needs_geocode.index, desc="Geocoding", unit="addr"):
            address = str(df.at[idx, "full_address"]).strip()
            if not address:
                fail_count += 1
                continue
            try:
                location = geocode(address)
                if location:
                    df.at[idx, lat_col] = location.latitude
                    df.at[idx, lng_col] = location.longitude
                    success_count += 1
                else:
                    fail_count += 1
            except Exception as exc:
                logger.debug("Geocoding failed for '%s': %s", address, exc)
                fail_count += 1

        logger.info("Geocoding complete: %d success, %d failed", success_count, fail_count)

    except ImportError:
        logger.warning("geopy not installed. Skipping geocoding.")
        logger.warning("Install with: pip install geopy")

    return df


# ---------------------------------------------------------------------------
# In-memory pipeline (for use by orchestrator)
# ---------------------------------------------------------------------------
def enrich_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    """Enrich a DataFrame in-memory without file I/O. Used by pipeline_orchestrator."""
    if len(df) == 0:
        return df

    # Geocode missing
    df = geocode_missing(df)

    # State/city standardization
    state_info = df.apply(resolve_state_info, axis=1)
    df["state_full"] = state_info.apply(lambda x: x[0])
    df["state_abbr_resolved"] = state_info.apply(lambda x: x[1])
    if "city" in df.columns:
        df["city_clean"] = df["city"].astype(str).str.strip().str.title()
    else:
        df["city_clean"] = ""
    df["state_slug"] = df["state_full"].apply(slugify)
    df["city_slug"] = df["city_clean"].apply(slugify)

    # Category assignment
    categories = df.get("extracted_services", pd.Series([""] * len(df))).apply(determine_categories)
    df["primary_category"] = categories.apply(lambda x: x[0])
    df["additional_categories"] = categories.apply(lambda x: x[1])

    # Description generation
    df["generated_description"] = df.apply(generate_description, axis=1)

    # Plan assignment
    df["plan_id"] = 50

    # Map option IDs
    df["services_offered_ids"] = df.get("extracted_services", pd.Series([""] * len(df))).apply(map_services_to_ids)
    df["panel_brands_ids"] = df.get("extracted_brands", pd.Series([""] * len(df))).apply(map_brands_to_ids)
    df["certifications_ids"] = df.get("extracted_certifications", pd.Series([""] * len(df))).apply(map_certs_to_ids)

    # Build output columns
    def safe_col(df, candidates, default=""):
        for c in candidates:
            if c in df.columns:
                return df[c]
        return pd.Series([default] * len(df))

    output = pd.DataFrame()
    output["company_name"] = safe_col(df, ["clean_name", "name"])
    output["company_description"] = df["generated_description"]
    output["phone"] = safe_col(df, ["phone_formatted", "phone"])
    output["email"] = safe_col(df, ["email"])
    output["website"] = safe_col(df, ["website_clean", "site", "website"])
    output["address"] = safe_col(df, ["full_address", "street", "address"])
    output["city"] = df["city_clean"]
    output["state"] = df["state_full"]
    output["zip_code"] = safe_col(df, ["postal_code", "zip_code", "zip"])
    output["latitude"] = safe_col(df, ["latitude", "lat"])
    output["longitude"] = safe_col(df, ["longitude", "lng"])
    output["google_rating"] = safe_col(df, ["rating", "google_rating"])
    output["total_reviews"] = safe_col(df, ["reviews", "reviews_count", "total_reviews"])
    output["services_offered"] = df["services_offered_ids"]
    output["panel_brands"] = df["panel_brands_ids"]
    output["certifications"] = df["certifications_ids"]
    output["financing_available"] = safe_col(df, ["financing_detected"]).apply(
        lambda x: 1 if str(x).strip() in ("1", "True", "true", "yes") else 0
    )
    output["years_in_business"] = safe_col(df, ["extracted_years"]).apply(
        lambda x: str(x).strip() if pd.notna(x) and str(x).strip().isdigit() else "0"
    )
    output["primary_category"] = df["primary_category"]
    output["plan_id"] = df["plan_id"]
    output["place_id"] = safe_col(df, ["place_id"])

    logger.info("enrich_dataframe: produced %d enriched records", len(output))
    return output


# ---------------------------------------------------------------------------
# Main pipeline (file-based)
# ---------------------------------------------------------------------------
def enrich_data(input_path: str, output_path: str) -> None:
    logger.info("Loading CSV: %s", input_path)
    try:
        df = pd.read_csv(input_path, low_memory=False)
    except FileNotFoundError:
        logger.error("Input file not found: %s", input_path)
        sys.exit(1)

    start_count = len(df)
    logger.info("Loaded %d records", start_count)

    # -----------------------------------------------------------------------
    # Step 1: Geocoding
    # -----------------------------------------------------------------------
    logger.info("Step 1 — Geocoding missing coordinates ...")
    df = geocode_missing(df)

    # -----------------------------------------------------------------------
    # Step 2: State/City standardization and slugs
    # -----------------------------------------------------------------------
    logger.info("Step 2 — Standardizing states and generating slugs ...")

    state_info = df.apply(resolve_state_info, axis=1)
    df["state_full"] = state_info.apply(lambda x: x[0])
    df["state_abbr_resolved"] = state_info.apply(lambda x: x[1])

    # City cleanup
    if "city" in df.columns:
        df["city_clean"] = df["city"].astype(str).str.strip().str.title()
    else:
        df["city_clean"] = ""

    # URL-friendly slugs
    df["state_slug"] = df["state_full"].apply(slugify)
    df["city_slug"] = df["city_clean"].apply(slugify)

    # -----------------------------------------------------------------------
    # Step 3: Category assignment
    # -----------------------------------------------------------------------
    logger.info("Step 3 — Assigning categories ...")
    categories = df.get("extracted_services", pd.Series([""] * len(df))).apply(determine_categories)
    df["primary_category"] = categories.apply(lambda x: x[0])
    df["additional_categories"] = categories.apply(lambda x: x[1])

    # -----------------------------------------------------------------------
    # Step 4: Description generation
    # -----------------------------------------------------------------------
    logger.info("Step 4 — Generating descriptions ...")
    tqdm.pandas(desc="Generating descriptions")
    df["generated_description"] = df.progress_apply(generate_description, axis=1)

    # -----------------------------------------------------------------------
    # Step 5: Assign default plan
    # -----------------------------------------------------------------------
    logger.info("Step 5 — Assigning default plan (free_listing, Plan_ID=50) ...")
    df["plan_id"] = 50

    # -----------------------------------------------------------------------
    # Step 6: Map option IDs for services, brands, certifications
    # -----------------------------------------------------------------------
    logger.info("Step 6 — Mapping option IDs ...")

    df["services_offered_ids"] = df.get(
        "extracted_services", pd.Series([""] * len(df))
    ).apply(map_services_to_ids)

    df["panel_brands_ids"] = df.get(
        "extracted_brands", pd.Series([""] * len(df))
    ).apply(map_brands_to_ids)

    df["certifications_ids"] = df.get(
        "extracted_certifications", pd.Series([""] * len(df))
    ).apply(map_certs_to_ids)

    # -----------------------------------------------------------------------
    # Step 7: Final column mapping for bulk_import.php
    # -----------------------------------------------------------------------
    logger.info("Step 7 — Building final output columns ...")

    # Resolve column names (handle variations)
    def safe_col(df, candidates, default=""):
        for c in candidates:
            if c in df.columns:
                return df[c]
        return pd.Series([default] * len(df))

    output = pd.DataFrame()
    output["company_name"] = safe_col(df, ["clean_name", "name"])
    output["description"] = df["generated_description"]
    output["phone"] = safe_col(df, ["phone_formatted", "phone"])
    output["email"] = safe_col(df, ["email"])
    output["website"] = safe_col(df, ["website_clean", "site", "website"])
    output["address"] = safe_col(df, ["full_address", "street", "address"])
    output["city"] = df["city_clean"]
    output["state"] = df["state_full"]
    output["state_abbr"] = df["state_abbr_resolved"]
    output["zip_code"] = safe_col(df, ["postal_code", "zip_code", "zip"])
    output["latitude"] = safe_col(df, ["latitude", "lat"])
    output["longitude"] = safe_col(df, ["longitude", "lng"])
    output["google_rating"] = safe_col(df, ["rating", "google_rating"])
    output["total_reviews"] = safe_col(df, ["reviews", "reviews_count", "total_reviews"])
    output["services_offered"] = df["services_offered_ids"]
    output["panel_brands"] = df["panel_brands_ids"]
    output["certifications"] = df["certifications_ids"]
    output["financing_available"] = safe_col(df, ["financing_detected"]).apply(
        lambda x: 1 if str(x).strip() in ("1", "True", "true", "yes") else 0
    )
    output["years_in_business"] = safe_col(df, ["extracted_years"]).apply(
        lambda x: str(x).strip() if pd.notna(x) and str(x).strip().isdigit() else "0"
    )
    output["installations_completed"] = ""  # Not available from scraping
    output["service_area_radius"] = "0"  # Default, option IDs 1-5
    output["system_size_range"] = "0"  # Default, option IDs 1-5
    output["warranty_years"] = "0"  # Default
    output["primary_category"] = df["primary_category"]
    output["plan_id"] = df["plan_id"]

    # Also keep slugs for URL generation
    output["state_slug"] = df["state_slug"]
    output["city_slug"] = df["city_slug"]

    # -----------------------------------------------------------------------
    # Statistics
    # -----------------------------------------------------------------------
    logger.info("=" * 60)
    logger.info("ENRICHMENT STATISTICS")
    logger.info("=" * 60)
    logger.info("  Total records:           %d", len(output))

    # Category breakdown
    cat_counts = output["primary_category"].value_counts()
    logger.info("  Category breakdown:")
    for cat, count in cat_counts.items():
        logger.info("    %-25s %d", cat, count)

    # State breakdown (top 10)
    state_counts = output["state"].value_counts().head(10)
    logger.info("  Top 10 states:")
    for state, count in state_counts.items():
        logger.info("    %-25s %d", state, count)

    # Enrichment coverage
    has_desc = (output["description"].astype(str).str.len() > 10).sum()
    has_coords = (pd.to_numeric(output["latitude"], errors="coerce").notna()).sum()
    has_services = (output["services_offered"].astype(str).str.len() > 0).sum()
    has_brands = (output["panel_brands"].astype(str).str.len() > 0).sum()
    has_certs = (output["certifications"].astype(str).str.len() > 0).sum()

    logger.info("  Enrichment coverage:")
    logger.info("    Description:             %d (%.1f%%)", has_desc, has_desc / len(output) * 100)
    logger.info("    Coordinates:             %d (%.1f%%)", has_coords, has_coords / len(output) * 100)
    logger.info("    Services mapped:         %d (%.1f%%)", has_services, has_services / len(output) * 100)
    logger.info("    Brands detected:         %d (%.1f%%)", has_brands, has_brands / len(output) * 100)
    logger.info("    Certifications:          %d (%.1f%%)", has_certs, has_certs / len(output) * 100)
    logger.info("=" * 60)

    # -----------------------------------------------------------------------
    # Save
    # -----------------------------------------------------------------------
    os.makedirs(os.path.dirname(output_path) or ".", exist_ok=True)
    output.to_csv(output_path, index=False)
    logger.info("Saved enriched data to: %s", output_path)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def main():
    parser = argparse.ArgumentParser(
        description="Enrich verified solar installer data for bulk import.",
    )
    parser.add_argument(
        "--input", "-i",
        required=True,
        help="Path to verified CSV file.",
    )
    parser.add_argument(
        "--output", "-o",
        required=True,
        help="Path to save enriched CSV output.",
    )
    args = parser.parse_args()

    logger.info("=" * 60)
    logger.info("Solar Installer Data Enrichment")
    logger.info("Started: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("=" * 60)

    enrich_data(args.input, args.output)

    logger.info("Done.")


if __name__ == "__main__":
    main()
