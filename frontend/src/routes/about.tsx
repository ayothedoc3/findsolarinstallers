import { createRoute } from "@tanstack/react-router";

import { rootRoute } from "./__root";
import { useMarketplaceData } from "@/lib/marketplace";
import { usePageTitle } from "@/lib/seo";

export const aboutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/about",
  component: AboutPage,
});

function AboutPage() {
  const { data } = useMarketplaceData();
  const launchState = data?.launch_state || "our launch market";

  usePageTitle("About");

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <div className="bg-white rounded-3xl border border-border p-8 md:p-10 space-y-5">
        <h1 className="font-heading text-4xl font-bold">About Find Solar Installers</h1>
        <p className="text-muted-foreground leading-relaxed">
          Find Solar Installers is being built as a focused local marketplace, not a generic
          nationwide directory. We are starting in {launchState} and working backward from a
          simple question: which local solar companies would actually pay to stand out because
          the leads and visibility are useful.
        </p>
        <p className="text-muted-foreground leading-relaxed">
          That means we are prioritizing verification, attribution, and clear proof over
          template-driven scale. Featured placements are managed manually while we validate the
          market with the first installer customers.
        </p>
      </div>
    </div>
  );
}
