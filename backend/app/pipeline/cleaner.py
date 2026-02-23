"""Cleans raw Outscraper Google Maps data for solar installers."""
import logging
import re

logger = logging.getLogger(__name__)

SOLAR_KEYWORDS = [
    "solar", "photovoltaic", " pv ", "renewable energy",
    "sun power", "sunpower", "green energy", "clean energy",
    "energy contractor", "energy company", "energy service",
    "panel", "inverter", "battery storage",
]

# Businesses that clearly aren't solar — reject these even from solar-query results
NON_SOLAR_KEYWORDS = [
    "nail salon", "hair salon", "restaurant", "pizza", "dentist",
    "plumber", "hvac only", "auto repair", "car wash", "pet ",
    "veterinary", "law firm", "attorney", "real estate agent",
]

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


def normalize_phone(raw_phone: str) -> str:
    if not raw_phone or not str(raw_phone).strip():
        return ""
    digits = re.sub(r"\D", "", str(raw_phone).strip())
    if len(digits) == 10:
        return f"+1{digits}"
    if len(digits) == 11 and digits.startswith("1"):
        return f"+{digits}"
    return ""


def clean_website(url: str) -> str:
    if not url or not str(url).strip():
        return ""
    url = str(url).strip()
    if not url.startswith(("http://", "https://")):
        url = "https://" + url
    return url.rstrip("/")


def clean_business_name(name: str) -> str:
    if not name or not str(name).strip():
        return ""
    return re.sub(r"\s+", " ", str(name).strip()).title()


def resolve_state(raw_state: str) -> tuple[str, str]:
    if not raw_state or not str(raw_state).strip():
        return ("", "")
    s = str(raw_state).strip()
    upper = s.upper()
    if upper in STATE_ABBR_TO_FULL:
        return (STATE_ABBR_TO_FULL[upper], upper)
    if upper in STATE_FULL_TO_ABBR:
        return (s.title(), STATE_FULL_TO_ABBR[upper])
    return (s.title(), s.upper()[:2])


def is_solar_related(record: dict) -> bool:
    """Check if a record is plausibly solar-related.

    Since we already query Outscraper for solar-specific terms, most results
    are relevant.  We only reject businesses that are clearly NOT solar.
    """
    searchable = ""
    for key in ["name", "type", "category", "types", "subtypes", "description"]:
        val = record.get(key)
        if val:
            if isinstance(val, list):
                searchable += " " + " ".join(str(v).lower() for v in val)
            else:
                searchable += " " + str(val).lower()

    # If it explicitly mentions solar/energy keywords, always keep it
    if any(kw in searchable for kw in SOLAR_KEYWORDS):
        return True

    # If it looks like a clearly unrelated business, reject it
    name_lower = str(record.get("name", "")).lower()
    if any(kw in name_lower for kw in NON_SOLAR_KEYWORDS):
        return False

    # Otherwise, trust the Outscraper search query — it was solar-specific
    return True


def clean_records(raw_records: list[dict]) -> list[dict]:
    """Clean a list of raw Outscraper records. Returns cleaned records."""
    if not raw_records:
        return []

    # Step 1: Filter junk
    records = []
    skip_counts = {"no_name": 0, "no_address": 0, "closed": 0, "non_us": 0}
    us_variants = {"US", "USA", "UNITED STATES", "UNITED STATES OF AMERICA"}
    for r in raw_records:
        name = str(r.get("name", "")).strip()
        if not name:
            skip_counts["no_name"] += 1
            continue
        address = str(r.get("full_address", "")).strip()
        if not address:
            skip_counts["no_address"] += 1
            continue
        status = str(r.get("business_status", "")).upper()
        if status in ("CLOSED_PERMANENTLY", "CLOSED_TEMPORARILY"):
            skip_counts["closed"] += 1
            continue
        country = str(r.get("country", "")).strip().upper()
        if country and country not in us_variants:
            skip_counts["non_us"] += 1
            continue
        records.append(r)

    logger.info("After junk filter: %d records (skipped: %s)", len(records), skip_counts)

    # Step 2: Solar filter
    records = [r for r in records if is_solar_related(r)]

    logger.info("After solar filter: %d records", len(records))

    # Step 3: Dedup by phone
    seen_phones = {}
    for r in records:
        phone = normalize_phone(str(r.get("phone", "")))
        r["_phone_e164"] = phone
        if phone:
            reviews = int(float(r.get("reviews", 0) or 0))
            if phone not in seen_phones or reviews > seen_phones[phone].get("_reviews", 0):
                r["_reviews"] = reviews
                seen_phones[phone] = r

    # Rebuild: keep records with unique phones (best by review count) + records with no phone
    phone_records = list(seen_phones.values())
    no_phone = [r for r in records if not r.get("_phone_e164")]
    records = phone_records + no_phone

    # Step 4: Dedup by normalized address
    def norm_addr(r):
        addr = re.sub(r"[^a-z0-9 ]", "", str(r.get("full_address", "")).lower()).strip()
        city = str(r.get("city", "")).lower().strip()
        state = str(r.get("state", "")).lower().strip()
        return f"{addr}|{city}|{state}"

    seen_addrs = {}
    for r in records:
        key = norm_addr(r)
        reviews = int(float(r.get("reviews", 0) or 0))
        if key not in seen_addrs or reviews > seen_addrs[key].get("_reviews", 0):
            r["_reviews"] = reviews
            seen_addrs[key] = r
    records = list(seen_addrs.values())

    # Step 5: Standardize
    cleaned = []
    for r in records:
        state_full, state_abbr = resolve_state(str(r.get("state", "")))
        cleaned.append({
            "name": clean_business_name(str(r.get("name", ""))),
            "full_address": str(r.get("full_address", "")).strip(),
            "city": str(r.get("city", "")).strip(),
            "state": state_full,
            "state_abbr": state_abbr,
            "phone": r.get("_phone_e164", ""),
            "website": clean_website(str(r.get("site", r.get("website", "")))),
            "rating": float(r.get("rating", 0) or 0),
            "reviews": int(float(r.get("reviews", 0) or 0)),
            "place_id": str(r.get("place_id", "")).strip(),
            "latitude": float(r.get("latitude", 0) or 0),
            "longitude": float(r.get("longitude", 0) or 0),
            "subtypes": str(r.get("subtypes", "")),
            "business_status": str(r.get("business_status", "")),
        })

    logger.info("Cleaned: %d -> %d records", len(raw_records), len(cleaned))
    return cleaned
