import { createRoute, useNavigate } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery } from "@tanstack/react-query";
import { Search, Star, MapPin, Shield } from "lucide-react";
import { api } from "@/lib/api";
import { useState, useEffect } from "react";

export const searchRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/search",
  validateSearch: (search: Record<string, unknown>) => ({
    q: (search.q as string) || "",
    state: (search.state as string) || "",
    services: (search.services as string) || "",
    min_rating: search.min_rating ? Number(search.min_rating) : undefined,
    financing: search.financing === "true" ? true : undefined,
    sort: (search.sort as string) || "rating",
    page: search.page ? Number(search.page) : 1,
  }),
  component: SearchPage,
});

interface ListingBrief {
  id: number;
  name: string;
  slug: string;
  city: string | null;
  state: string | null;
  google_rating: number | null;
  total_reviews: number;
  services_offered: string[];
  certifications: string[];
  financing_available: boolean;
  primary_image: string | null;
}

interface SearchResults {
  items: ListingBrief[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

function SearchPage() {
  const search = searchRoute.useSearch();
  const navigate = useNavigate({ from: "/search" });

  const [locationInput, setLocationInput] = useState(search.state || search.q || "");
  const [selectedServices, setSelectedServices] = useState<string[]>(
    search.services ? search.services.split(",") : []
  );
  const [minRating, setMinRating] = useState<number | undefined>(search.min_rating);
  const [financing, setFinancing] = useState(search.financing || false);
  const [sort, setSort] = useState(search.sort || "rating");

  // Build query params
  const queryParams = new URLSearchParams();
  if (search.q) queryParams.set("q", search.q);
  if (search.state) queryParams.set("state", search.state);
  if (search.services) {
    search.services.split(",").forEach((s) => queryParams.append("services", s));
  }
  if (search.min_rating) queryParams.set("min_rating", String(search.min_rating));
  if (search.financing) queryParams.set("financing", "true");
  queryParams.set("sort", search.sort || "rating");
  queryParams.set("page", String(search.page || 1));
  queryParams.set("per_page", "20");

  const { data, isLoading } = useQuery<SearchResults>({
    queryKey: ["listings", queryParams.toString()],
    queryFn: () => api.get(`/listings?${queryParams.toString()}`),
  });

  const applyFilters = () => {
    navigate({
      search: {
        q: locationInput || "",
        state: "",
        services: selectedServices.join(","),
        min_rating: minRating,
        financing: financing ? "true" : undefined,
        sort,
        page: 1,
      } as any,
    });
  };

  const toggleService = (service: string) => {
    setSelectedServices((prev) =>
      prev.includes(service) ? prev.filter((s) => s !== service) : [...prev, service]
    );
  };

  const allServices = [
    "Residential", "Commercial", "Battery Storage",
    "EV Charger", "Maintenance", "Pool/Water Heating",
  ];

  return (
    <div className="max-w-6xl mx-auto px-4 py-8">
      <h1 className="font-heading text-3xl font-bold mb-8">
        Find Solar Installers
        {search.state && <span className="text-accent"> in {search.state}</span>}
      </h1>

      <div className="grid lg:grid-cols-4 gap-8">
        {/* Filters Sidebar */}
        <aside className="lg:col-span-1 space-y-6">
          <div className="bg-white rounded-xl border border-border p-4">
            <h3 className="font-semibold mb-3">Location</h3>
            <input
              type="text"
              value={locationInput}
              onChange={(e) => setLocationInput(e.target.value)}
              placeholder="State or city..."
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
              onKeyDown={(e) => e.key === "Enter" && applyFilters()}
            />
          </div>

          <div className="bg-white rounded-xl border border-border p-4">
            <h3 className="font-semibold mb-3">Services</h3>
            <div className="space-y-2">
              {allServices.map((s) => (
                <label key={s} className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="checkbox"
                    checked={selectedServices.includes(s)}
                    onChange={() => toggleService(s)}
                    className="rounded border-border"
                  />
                  <span>{s}</span>
                </label>
              ))}
            </div>
          </div>

          <div className="bg-white rounded-xl border border-border p-4">
            <h3 className="font-semibold mb-3">Minimum Rating</h3>
            <div className="space-y-2">
              {[4.5, 4.0, 3.5, 3.0].map((r) => (
                <label key={r} className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="radio"
                    name="rating"
                    checked={minRating === r}
                    onChange={() => setMinRating(r)}
                    className="border-border"
                  />
                  <span className="flex items-center gap-1">
                    <Star className="w-4 h-4 fill-accent text-accent" /> {r}+
                  </span>
                </label>
              ))}
            </div>
          </div>

          <div className="bg-white rounded-xl border border-border p-4">
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input
                type="checkbox"
                checked={financing}
                onChange={(e) => setFinancing(e.target.checked)}
                className="rounded border-border"
              />
              <span>Financing Available</span>
            </label>
          </div>

          <button
            onClick={applyFilters}
            className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-2.5 rounded-lg transition-colors text-sm"
          >
            Apply Filters
          </button>
        </aside>

        {/* Results */}
        <section className="lg:col-span-3">
          <div className="flex items-center justify-between mb-6">
            <p className="text-muted-foreground text-sm">
              {isLoading ? "Searching..." : `${data?.total ?? 0} results found`}
            </p>
            <select
              value={sort}
              onChange={(e) => {
                setSort(e.target.value);
                navigate({ search: { ...search, sort: e.target.value, page: 1 } as any });
              }}
              className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
            >
              <option value="rating">Highest Rated</option>
              <option value="newest">Newest</option>
              <option value="name">Name A-Z</option>
            </select>
          </div>

          {isLoading ? (
            <div className="space-y-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="bg-white rounded-xl border border-border p-6 animate-pulse">
                  <div className="flex gap-4">
                    <div className="w-24 h-24 bg-muted rounded-xl shrink-0" />
                    <div className="flex-1 space-y-3">
                      <div className="h-5 bg-muted rounded w-1/3" />
                      <div className="h-4 bg-muted rounded w-1/4" />
                      <div className="h-4 bg-muted rounded w-2/3" />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : data?.items?.length === 0 ? (
            <div className="bg-white rounded-xl border border-border p-12 text-center">
              <Search className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="font-heading text-xl font-semibold mb-2">No results found</h3>
              <p className="text-muted-foreground">Try adjusting your filters or search in a different location.</p>
            </div>
          ) : (
            <div className="space-y-4">
              {data?.items?.map((listing) => (
                <a
                  key={listing.id}
                  href={`/listing/${listing.slug}`}
                  className="block bg-white rounded-xl border border-border p-6 hover:shadow-md hover:border-accent/30 transition-all"
                >
                  <div className="flex gap-4">
                    <div className="w-24 h-24 bg-muted rounded-xl shrink-0 overflow-hidden">
                      {listing.primary_image && (
                        <img src={listing.primary_image} alt={listing.name} className="w-full h-full object-cover" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="font-heading text-lg font-semibold mb-1 truncate">
                        {listing.name}
                      </h3>
                      <div className="flex items-center gap-2 text-sm text-muted-foreground mb-2">
                        {(listing.city || listing.state) && (
                          <>
                            <MapPin className="w-4 h-4 shrink-0" />
                            <span>{[listing.city, listing.state].filter(Boolean).join(", ")}</span>
                          </>
                        )}
                        {listing.google_rating && (
                          <span className="flex items-center gap-1 ml-2">
                            <Star className="w-4 h-4 fill-accent text-accent" />
                            {listing.google_rating} ({listing.total_reviews} reviews)
                          </span>
                        )}
                      </div>
                      <div className="flex flex-wrap gap-1.5 mb-3">
                        {listing.services_offered.slice(0, 3).map((s) => (
                          <span key={s} className="text-xs px-2 py-0.5 rounded-full bg-accent/10 text-accent font-medium">
                            {s}
                          </span>
                        ))}
                        {listing.certifications.slice(0, 2).map((c) => (
                          <span key={c} className="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium inline-flex items-center gap-1">
                            <Shield className="w-3 h-3" /> {c}
                          </span>
                        ))}
                        {listing.financing_available && (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">
                            Financing
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </a>
              ))}
            </div>
          )}

          {/* Pagination */}
          {data && data.pages > 1 && (
            <div className="flex justify-center gap-2 mt-8">
              {Array.from({ length: Math.min(data.pages, 10) }, (_, i) => i + 1).map((p) => (
                <button
                  key={p}
                  onClick={() => navigate({ search: { ...search, page: p } as any })}
                  className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                    p === data.page
                      ? "bg-primary text-primary-foreground"
                      : "bg-white border border-border hover:bg-muted"
                  }`}
                >
                  {p}
                </button>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
