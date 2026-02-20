from geoalchemy2.functions import ST_DWithin, ST_MakePoint, ST_SetSRID


def make_point(lng: float, lat: float):
    """Create a PostGIS geography point from longitude and latitude."""
    return ST_SetSRID(ST_MakePoint(lng, lat), 4326)


def within_radius(column, lng: float, lat: float, radius_meters: float):
    """Filter for records within a radius of a point."""
    point = make_point(lng, lat)
    return ST_DWithin(column, point, radius_meters)


MILES_TO_METERS = 1609.344
