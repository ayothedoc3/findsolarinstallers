"""Programmatic SEO utilities: slug generation/parsing, content templates."""

import re
from typing import TypedDict

# ── State mappings ──────────────────────────────────────────────────────────

STATES: dict[str, str] = {
    "AL": "Alabama", "AK": "Alaska", "AZ": "Arizona", "AR": "Arkansas",
    "CA": "California", "CO": "Colorado", "CT": "Connecticut", "DE": "Delaware",
    "DC": "District of Columbia", "FL": "Florida", "GA": "Georgia", "HI": "Hawaii",
    "ID": "Idaho", "IL": "Illinois", "IN": "Indiana", "IA": "Iowa",
    "KS": "Kansas", "KY": "Kentucky", "LA": "Louisiana", "ME": "Maine",
    "MD": "Maryland", "MA": "Massachusetts", "MI": "Michigan", "MN": "Minnesota",
    "MS": "Mississippi", "MO": "Missouri", "MT": "Montana", "NE": "Nebraska",
    "NV": "Nevada", "NH": "New Hampshire", "NJ": "New Jersey", "NM": "New Mexico",
    "NY": "New York", "NC": "North Carolina", "ND": "North Dakota", "OH": "Ohio",
    "OK": "Oklahoma", "OR": "Oregon", "PA": "Pennsylvania", "RI": "Rhode Island",
    "SC": "South Carolina", "SD": "South Dakota", "TN": "Tennessee", "TX": "Texas",
    "UT": "Utah", "VT": "Vermont", "VA": "Virginia", "WA": "Washington",
    "WV": "West Virginia", "WI": "Wisconsin", "WY": "Wyoming",
}

STATE_NAME_TO_CODE: dict[str, str] = {v.lower(): k for k, v in STATES.items()}
STATE_SLUG_TO_CODE: dict[str, str] = {v.lower().replace(" ", "-"): k for k, v in STATES.items()}

# ── Service mappings ────────────────────────────────────────────────────────

SERVICE_SLUGS: dict[str, str] = {
    "residential": "Residential",
    "commercial": "Commercial",
    "battery-storage": "Battery Storage",
    "ev-charger": "EV Charger",
    "maintenance": "Maintenance",
    "pool-heating": "Pool/Water Heating",
}

SERVICE_TO_SLUG: dict[str, str] = {v: k for k, v in SERVICE_SLUGS.items()}


# ── Parsed slug result ──────────────────────────────────────────────────────

class ParsedSlug(TypedDict, total=False):
    page_type: str          # state, city, service_state, service_city, zip
    service: str | None     # Display name e.g. "Residential"
    service_slug: str | None
    city: str | None        # Title case e.g. "Denver"
    state: str | None       # Full name e.g. "Colorado"
    state_code: str | None  # "CO"
    zip_code: str | None


def parse_pseo_slug(slug: str) -> ParsedSlug | None:
    """Parse a pSEO slug into its components. Returns None if invalid."""
    slug = slug.strip("/").lower()

    # Pattern: [service-]solar-installers-in-[location] or near-[zip]
    # e.g. residential-solar-installers-in-denver-co
    # e.g. solar-installers-in-california
    # e.g. solar-installers-near-90210

    # Try ZIP pattern first
    m = re.match(r"^(?:([\w-]+)-)?solar-installers-near-(\d{5})$", slug)
    if m:
        service_slug = m.group(1)
        service = SERVICE_SLUGS.get(service_slug) if service_slug else None
        if service_slug and not service:
            return None
        return ParsedSlug(
            page_type="zip",
            service=service,
            service_slug=service_slug,
            city=None, state=None, state_code=None,
            zip_code=m.group(2),
        )

    # Try "in" pattern
    m = re.match(r"^(?:([\w-]+)-)?solar-installers-in-([\w-]+)$", slug)
    if not m:
        return None

    service_slug = m.group(1)
    service = SERVICE_SLUGS.get(service_slug) if service_slug else None
    if service_slug and not service:
        return None

    location_part = m.group(2)  # e.g. "denver-co" or "california" or "new-york"

    # Check if ends with a 2-letter state code → city + state
    parts = location_part.rsplit("-", 1)
    if len(parts) == 2 and len(parts[1]) == 2 and parts[1].upper() in STATES:
        state_code = parts[1].upper()
        city_slug = parts[0]
        city = city_slug.replace("-", " ").title()
        state = STATES[state_code]
        page_type = "service_city" if service else "city"
        return ParsedSlug(
            page_type=page_type,
            service=service, service_slug=service_slug,
            city=city, state=state, state_code=state_code,
            zip_code=None,
        )

    # Check if it's a state name slug (e.g. "california", "new-york")
    if location_part in STATE_SLUG_TO_CODE:
        state_code = STATE_SLUG_TO_CODE[location_part]
        state = STATES[state_code]
        page_type = "service_state" if service else "state"
        return ParsedSlug(
            page_type=page_type,
            service=service, service_slug=service_slug,
            city=None, state=state, state_code=state_code,
            zip_code=None,
        )

    return None


# ── Slug generation ─────────────────────────────────────────────────────────

def make_pseo_slug(
    *,
    service: str | None = None,
    city: str | None = None,
    state: str | None = None,
    state_code: str | None = None,
    zip_code: str | None = None,
) -> str | None:
    """Generate a canonical pSEO slug from components."""
    prefix = ""
    if service:
        s = SERVICE_TO_SLUG.get(service)
        if not s:
            return None
        prefix = f"{s}-"

    if zip_code:
        return f"{prefix}solar-installers-near-{zip_code}"

    if city and (state_code or state):
        sc = state_code or STATE_NAME_TO_CODE.get(state.lower(), "")
        if not sc:
            return None
        city_slug = city.lower().replace(" ", "-")
        return f"{prefix}solar-installers-in-{city_slug}-{sc.lower()}"

    if state:
        state_slug = state.lower().replace(" ", "-")
        return f"{prefix}solar-installers-in-{state_slug}"

    if state_code:
        full = STATES.get(state_code.upper())
        if not full:
            return None
        return f"{prefix}solar-installers-in-{full.lower().replace(' ', '-')}"

    return None


# ── Content generation ──────────────────────────────────────────────────────

def generate_title(parsed: ParsedSlug) -> str:
    svc = f"{parsed.get('service', '') or ''} ".strip()
    if svc:
        svc += " "
    loc = _location_str(parsed)
    return f"Best {svc}Solar Installers in {loc} | Free Quotes"


def generate_h1(parsed: ParsedSlug) -> str:
    svc = parsed.get("service") or ""
    if svc:
        svc += " "
    loc = _location_str(parsed)
    return f"Top {svc}Solar Installers in {loc} — Rated & Verified"


def generate_meta_description(parsed: ParsedSlug, count: int, avg_rating: float | None) -> str:
    svc = (parsed.get("service") or "").lower()
    if svc:
        svc += " "
    loc = _location_str(parsed)
    rating_str = f" Average {avg_rating:.1f}-star rating." if avg_rating else ""
    return (
        f"Compare {count} {svc}solar installers in {loc}.{rating_str} "
        f"Read verified reviews, check certifications, and get free quotes for your solar project."
    )


def generate_faqs(parsed: ParsedSlug, count: int, avg_rating: float | None) -> list[dict[str, str]]:
    loc = _location_str(parsed)
    svc = (parsed.get("service") or "solar").lower()
    state = parsed.get("state") or loc
    faqs = [
        {
            "q": f"How many {svc} solar installers are in {loc}?",
            "a": f"We currently list {count} verified {svc} solar installers in {loc}. "
                 f"Each company is rated based on Google reviews and certifications.",
        },
        {
            "q": f"What is the average rating of solar installers in {loc}?",
            "a": f"Solar installers in {loc} have an average rating of {avg_rating:.1f} stars "
                 f"based on verified Google reviews." if avg_rating else
                 f"Ratings for solar installers in {loc} are based on verified Google reviews. "
                 f"Browse individual listings for detailed ratings.",
        },
        {
            "q": f"How much do solar panels cost in {state}?",
            "a": f"Solar panel costs in {state} vary based on system size, panel brand, and installer. "
                 f"The average residential system costs $15,000–$30,000 before incentives. "
                 f"Get free quotes from local installers to compare prices.",
        },
        {
            "q": f"What solar incentives are available in {state}?",
            "a": f"Homeowners in {state} can benefit from the federal 30% solar Investment Tax Credit (ITC), "
                 f"plus potential state rebates, net metering programs, and local incentives. "
                 f"Ask your installer about available incentives.",
        },
        {
            "q": f"How do I choose the best solar installer in {loc}?",
            "a": f"Compare ratings, read customer reviews, check certifications (NABCEP, Tesla Certified), "
                 f"verify licensing, and get multiple quotes. Our directory makes it easy to compare "
                 f"{count} installers in {loc} side by side.",
        },
    ]
    return faqs


def _location_str(parsed: ParsedSlug) -> str:
    if parsed.get("zip_code"):
        return parsed["zip_code"]
    city = parsed.get("city")
    state = parsed.get("state")
    state_code = parsed.get("state_code")
    if city and state_code:
        return f"{city}, {state_code}"
    if city and state:
        return f"{city}, {state}"
    return state or ""
