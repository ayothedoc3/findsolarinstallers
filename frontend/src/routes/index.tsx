import { createRoute, useNavigate } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery } from "@tanstack/react-query";
import { Search, Sun, Shield, Star, Zap, MapPin, ArrowRight, ChevronRight } from "lucide-react";
import { Link } from "@tanstack/react-router";
import { api } from "@/lib/api";
import { useState } from "react";
import { usePageTitle } from "@/lib/seo";

export const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/",
  component: HomePage,
});

function HomePage() {
  usePageTitle("");
  const [searchQuery, setSearchQuery] = useState("");
  const navigate = useNavigate();

  const { data: stats } = useQuery({
    queryKey: ["stats"],
    queryFn: () => api.get<{
      total_listings: number;
      total_states: number;
      total_reviews: number;
      launch_state: string;
      active_featured_count: number;
    }>("/stats"),
  });
  const launchState = stats?.launch_state || "our launch market";

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    navigate({ to: "/search", search: { q: searchQuery } as any });
  };

  return (
    <>
      {/* Hero Section */}
      <section className="bg-primary text-primary-foreground py-20 px-4">
        <div className="max-w-5xl mx-auto text-center">
          <h1 className="font-heading text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
            Find Trusted Solar Installers in{" "}
            <span className="text-accent">{launchState}</span>
          </h1>
          <p className="text-lg md:text-xl text-slate-300 mb-10 max-w-2xl mx-auto">
            We are focusing the marketplace on {launchState} first. Compare local solar
            companies, prioritize verified featured profiles, and request quotes from real
            installers instead of browsing a generic nationwide directory.
          </p>

          {/* Search Bar */}
          <form onSubmit={handleSearch} className="bg-white rounded-xl p-2 flex flex-col sm:flex-row gap-2 max-w-2xl mx-auto shadow-lg">
            <div className="flex-1 flex items-center gap-2 px-4">
              <Search className="w-5 h-5 text-muted-foreground" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="City, state, or ZIP code..."
                className="w-full py-3 bg-transparent text-foreground placeholder:text-muted-foreground outline-none"
              />
            </div>
            <button type="submit" className="bg-accent hover:bg-accent/90 text-accent-foreground font-semibold px-8 py-3 rounded-lg transition-colors">
              Search
            </button>
          </form>
        </div>
      </section>

      {/* Stats Bar */}
      <section className="bg-white border-b border-border py-8">
        <div className="max-w-5xl mx-auto grid grid-cols-3 gap-4 text-center px-4">
          <div>
            <div className="text-3xl font-bold text-primary font-heading">{stats?.total_listings?.toLocaleString() ?? "0"}</div>
            <div className="text-muted-foreground text-sm">Live Profiles</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-primary font-heading">{stats?.active_featured_count ?? 0}</div>
            <div className="text-muted-foreground text-sm">Featured Installers</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-primary font-heading">{launchState}</div>
            <div className="text-muted-foreground text-sm">Launch State</div>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-16 px-4">
        <div className="max-w-5xl mx-auto">
          <h2 className="font-heading text-3xl font-bold text-center mb-12">
            How It Works
          </h2>
          <div className="grid md:grid-cols-3 gap-8">
            {[
              { icon: Search, title: "Search", desc: "Enter your city or ZIP code to find solar installers in your area." },
              { icon: Star, title: "Compare", desc: "Review ratings, certifications, services, and customer reviews." },
              { icon: Sun, title: "Get Quotes", desc: "Contact installers directly and get free quotes for your project." },
            ].map((step, i) => (
              <div key={i} className="text-center">
                <div className="w-16 h-16 bg-accent/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                  <step.icon className="w-8 h-8 text-accent" />
                </div>
                <h3 className="font-heading text-xl font-semibold mb-2">{step.title}</h3>
                <p className="text-muted-foreground">{step.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Browse by Service */}
      <section className="py-16 px-4 bg-white">
        <div className="max-w-5xl mx-auto">
          <h2 className="font-heading text-3xl font-bold text-center mb-12">
            Browse by Service
          </h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {[
              { icon: Sun, name: "Residential Solar", slug: "residential-solar" },
              { icon: Zap, name: "Commercial Solar", slug: "commercial-solar" },
              { icon: Shield, name: "Solar Maintenance", slug: "solar-maintenance" },
              { icon: Zap, name: "Battery Storage", slug: "solar-battery-storage" },
              { icon: Sun, name: "Solar Pool Heating", slug: "solar-pool-heating" },
              { icon: Zap, name: "EV Charger + Solar", slug: "ev-charger-solar" },
            ].map((service) => (
              <a
                key={service.slug}
                href={`/search?services=${service.name}`}
                className="flex items-center gap-4 p-4 rounded-xl border border-border hover:border-accent hover:shadow-md transition-all group"
              >
                <div className="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center shrink-0">
                  <service.icon className="w-6 h-6 text-accent" />
                </div>
                <div className="flex-1">
                  <div className="font-semibold group-hover:text-accent transition-colors">{service.name}</div>
                </div>
                <ChevronRight className="w-5 h-5 text-muted-foreground group-hover:text-accent transition-colors" />
              </a>
            ))}
          </div>
        </div>
      </section>

      {/* Popular Locations */}
      <section className="py-16 px-4">
        <div className="max-w-5xl mx-auto">
          <h2 className="font-heading text-3xl font-bold text-center mb-12">
            Why We Are Starting in {launchState}
          </h2>
          <div className="grid sm:grid-cols-3 gap-4">
            {[
              "Local density beats fake nationwide scale",
              "Featured profiles rank ahead of generic listings",
              "Quote requests stay attributable to the profile page",
            ].map((reason) => (
              <div
                key={reason}
                className="flex items-center gap-3 p-5 rounded-xl bg-white border border-border"
              >
                <MapPin className="w-5 h-5 text-accent shrink-0" />
                <span className="font-medium">{reason}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="bg-primary text-primary-foreground py-16 px-4">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="font-heading text-3xl font-bold mb-4">
            Are You a Solar Installer in {launchState}?
          </h2>
          <p className="text-lg text-slate-300 mb-8">
            Featured spots are being sold manually. Claim your profile, get verified, and
            stand out before the launch market gets crowded.
          </p>
          <Link
            to="/for-installers"
            search={{ listing: "", state: "" }}
            className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground font-semibold px-8 py-4 rounded-xl transition-colors text-lg"
          >
            Get Featured in {launchState} <ArrowRight className="w-5 h-5" />
          </Link>
        </div>
      </section>
    </>
  );
}
