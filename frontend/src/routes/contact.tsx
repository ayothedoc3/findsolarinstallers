import { createRoute } from "@tanstack/react-router";

import { rootRoute } from "./__root";
import { useMarketplaceData } from "@/lib/marketplace";
import { usePageTitle } from "@/lib/seo";

export const contactRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/contact",
  component: ContactPage,
});

function ContactPage() {
  const { data } = useMarketplaceData();

  usePageTitle("Contact");

  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <div className="bg-white rounded-3xl border border-border p-8 md:p-10 space-y-5">
        <h1 className="font-heading text-4xl font-bold">Contact</h1>
        <p className="text-muted-foreground">
          For installer onboarding, profile corrections, or partnership questions, contact us
          directly.
        </p>
        <div className="rounded-2xl bg-muted p-5">
          <div className="text-sm text-muted-foreground mb-1">Email</div>
          <a href={`mailto:${data?.contact_email || "info@findsolarinstallers.xyz"}`} className="text-lg font-semibold text-foreground hover:text-accent">
            {data?.contact_email || "info@findsolarinstallers.xyz"}
          </a>
        </div>
        <p className="text-sm text-muted-foreground">
          Homeowner quote requests should be submitted from the installer profile page so they
          can be attributed correctly.
        </p>
      </div>
    </div>
  );
}
