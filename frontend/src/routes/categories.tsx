import { createRoute } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery } from "@tanstack/react-query";
import { Sun, Zap, Shield, ChevronRight } from "lucide-react";
import { api } from "@/lib/api";
import { useMarketplaceData } from "@/lib/marketplace";
import { usePageTitle } from "@/lib/seo";

export const categoriesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/categories",
  component: CategoriesPage,
});

interface Category {
  id: number;
  parent_id: number | null;
  name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  sort_order: number;
  listing_count: number;
}

const iconMap: Record<string, typeof Sun> = {
  Sun, Zap, Shield,
};

function getIcon(name: string | null) {
  if (name && iconMap[name]) return iconMap[name];
  return Sun;
}

function CategoriesPage() {
  usePageTitle("Solar Service Categories");
  const { data: marketplace } = useMarketplaceData();
  const launchState = marketplace?.launch_state || "our launch market";

  const { data: categories, isLoading } = useQuery<Category[]>({
    queryKey: ["categories"],
    queryFn: () => api.get("/categories"),
  });

  const root = categories?.find((c) => !c.parent_id);
  const subcategories = categories?.filter((c) => c.parent_id) ?? [];

  return (
    <div className="max-w-5xl mx-auto px-4 py-12">
      <div className="text-center mb-12">
        <h1 className="font-heading text-4xl font-bold mb-4">
          Browse Solar Services
        </h1>
        <p className="text-muted-foreground text-lg max-w-2xl mx-auto">
          Explore the service types we are prioritizing in {launchState}, then compare the
          local installers that actually match your project.
        </p>
      </div>

      {isLoading ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div
              key={i}
              className="bg-white rounded-xl border border-border p-6 animate-pulse"
            >
              <div className="w-14 h-14 bg-muted rounded-xl mb-4" />
              <div className="h-5 bg-muted rounded w-2/3 mb-2" />
              <div className="h-4 bg-muted rounded w-full mb-1" />
              <div className="h-4 bg-muted rounded w-1/2" />
            </div>
          ))}
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {subcategories.map((cat) => {
            const Icon = getIcon(cat.icon);
            return (
              <a
                key={cat.id}
                href={`/search?services=${encodeURIComponent(cat.name)}`}
                className="group bg-white rounded-xl border border-border p-6 hover:shadow-lg hover:border-accent/40 transition-all"
              >
                <div className="w-14 h-14 bg-accent/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-accent/20 transition-colors">
                  <Icon className="w-7 h-7 text-accent" />
                </div>
                <h2 className="font-heading text-xl font-semibold mb-2 group-hover:text-accent transition-colors">
                  {cat.name}
                </h2>
                {cat.description && (
                  <p className="text-muted-foreground text-sm mb-3 line-clamp-2">
                    {cat.description}
                  </p>
                )}
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">
                    {cat.listing_count}{" "}
                    {cat.listing_count === 1 ? "installer" : "installers"}
                  </span>
                  <ChevronRight className="w-5 h-5 text-muted-foreground group-hover:text-accent transition-colors" />
                </div>
              </a>
            );
          })}
        </div>
      )}

      {/* Root category info */}
      {root && (
        <div className="mt-16 text-center">
          <div className="bg-primary text-primary-foreground rounded-2xl p-10">
            <h2 className="font-heading text-2xl font-bold mb-3">
              Get Featured in {launchState}
            </h2>
            <p className="text-slate-300 mb-6 max-w-xl mx-auto">
              If you install solar in {launchState}, we are manually onboarding featured
              profiles now.
            </p>
            <a
              href="/for-installers"
              className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground font-semibold px-6 py-3 rounded-xl transition-colors"
            >
              Claim Your Profile <ChevronRight className="w-5 h-5" />
            </a>
          </div>
        </div>
      )}
    </div>
  );
}
