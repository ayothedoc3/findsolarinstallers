"""Enriches cleaned solar installer data with descriptions and category mapping."""
import logging
import re

logger = logging.getLogger(__name__)

SERVICE_KEYWORDS = {
    "Residential Solar": ["residential", "home", "house", "rooftop"],
    "Commercial Solar": ["commercial", "business", "industrial", "enterprise"],
    "Solar Maintenance": ["maintenance", "repair", "cleaning", "service", "inspection"],
    "Solar Battery Storage": ["battery", "storage", "powerwall", "backup", "enphase"],
    "Solar Pool Heating": ["pool", "heating", "thermal", "hot water"],
    "EV Charger + Solar": ["ev charger", "electric vehicle", "charging station", "ev"],
}

CERT_KEYWORDS = {
    "NABCEP": ["nabcep"],
    "SEIA Member": ["seia"],
    "BBB Accredited": ["bbb", "better business"],
    "Tesla Certified": ["tesla", "powerwall certified"],
    "Enphase Partner": ["enphase"],
    "SunPower Dealer": ["sunpower dealer", "sunpower master"],
}

BRAND_KEYWORDS = {
    "SunPower": ["sunpower"],
    "Tesla": ["tesla"],
    "LG Solar": ["lg solar", "lg energy"],
    "Panasonic": ["panasonic"],
    "Canadian Solar": ["canadian solar"],
    "Jinko Solar": ["jinko"],
    "Trina Solar": ["trina"],
    "Q CELLS": ["qcells", "q cells", "q-cells"],
    "REC Solar": ["rec solar", "rec group"],
    "SolarEdge": ["solaredge"],
    "Enphase": ["enphase"],
}


def slugify(text: str) -> str:
    if not text or not str(text).strip():
        return ""
    slug = str(text).strip().lower()
    slug = re.sub(r"[^a-z0-9\s-]", "", slug)
    slug = re.sub(r"[\s_]+", "-", slug)
    return re.sub(r"-+", "-", slug).strip("-")


def detect_services(record: dict) -> list[str]:
    searchable = " ".join(str(v) for v in [
        record.get("name", ""), record.get("subtypes", ""),
        record.get("website", ""),
    ]).lower()
    services = []
    for service_name, keywords in SERVICE_KEYWORDS.items():
        if any(kw in searchable for kw in keywords):
            services.append(service_name)
    if not services:
        services.append("Residential Solar")
    return services


def detect_certifications(record: dict) -> list[str]:
    searchable = " ".join(str(v) for v in [
        record.get("name", ""), record.get("subtypes", ""),
    ]).lower()
    return [cert for cert, kws in CERT_KEYWORDS.items() if any(kw in searchable for kw in kws)]


def detect_brands(record: dict) -> list[str]:
    searchable = " ".join(str(v) for v in [
        record.get("name", ""), record.get("subtypes", ""),
    ]).lower()
    return [brand for brand, kws in BRAND_KEYWORDS.items() if any(kw in searchable for kw in kws)]


def generate_description(record: dict) -> str:
    name = record.get("name", "This company")
    city = record.get("city", "")
    state = record.get("state", "")
    location = ", ".join(filter(None, [city, state])) or "the local area"
    services = record.get("services_offered", ["solar installation"])
    if len(services) == 1:
        svc_str = services[0].lower()
    elif len(services) == 2:
        svc_str = f"{services[0].lower()} and {services[1].lower()}"
    else:
        svc_str = ", ".join(s.lower() for s in services[:-1]) + f", and {services[-1].lower()}"

    desc = f"{name} is a solar installation company serving {location}. They specialize in {svc_str}."
    rating = record.get("rating", 0)
    reviews = record.get("reviews", 0)
    if rating and float(rating) > 0:
        desc += f" They have a {float(rating):.1f}-star Google rating"
        if reviews and int(reviews) > 0:
            desc += f" based on {int(reviews)} reviews"
        desc += "."
    return desc


def enrich_records(cleaned_records: list[dict]) -> list[dict]:
    """Enrich cleaned records with services, certs, brands, descriptions, slugs."""
    enriched = []
    seen_slugs = set()

    for record in cleaned_records:
        services = detect_services(record)
        certs = detect_certifications(record)
        brands = detect_brands(record)

        record["services_offered"] = services
        record["certifications"] = certs
        record["panel_brands"] = brands

        record["description"] = generate_description(record)

        base_slug = slugify(record.get("name", ""))
        if record.get("city"):
            base_slug += "-" + slugify(record["city"])
        if record.get("state_abbr"):
            base_slug += "-" + record["state_abbr"].lower()

        slug = base_slug
        counter = 1
        while slug in seen_slugs:
            slug = f"{base_slug}-{counter}"
            counter += 1
        seen_slugs.add(slug)
        record["slug"] = slug

        enriched.append(record)

    logger.info("Enriched %d records", len(enriched))
    return enriched
