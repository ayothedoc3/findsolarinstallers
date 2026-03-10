import { createRoute } from "@tanstack/react-router";

import { rootRoute } from "./__root";
import { usePageTitle } from "@/lib/seo";

export const privacyRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/privacy",
  component: PrivacyPage,
});

function PrivacyPage() {
  usePageTitle("Privacy");

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <div className="bg-white rounded-3xl border border-border p-8 md:p-10 space-y-5">
        <h1 className="font-heading text-4xl font-bold">Privacy Policy</h1>
        <p className="text-muted-foreground">
          We collect the information necessary to operate installer profiles, attribute quote
          requests, and measure listing performance. Quote requests may include your name, email,
          phone number, project details, and marketing attribution data such as the page path and
          UTM tags that led to the request.
        </p>
        <p className="text-muted-foreground">
          We use lightweight analytics to understand which pages are working. We do not rely on
          invasive ad-tech or cookie-heavy tracking for the core directory experience.
        </p>
        <p className="text-muted-foreground">
          If you need a profile removed or corrected, contact us using the contact page.
        </p>
      </div>
    </div>
  );
}
