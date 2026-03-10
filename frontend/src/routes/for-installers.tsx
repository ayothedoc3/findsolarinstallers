import { createRoute } from "@tanstack/react-router";
import { useMutation } from "@tanstack/react-query";
import { ArrowRight, BadgeCheck, CheckCircle2, Mail, Phone } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { rootRoute } from "./__root";
import { api } from "@/lib/api";
import { getUtmPayload, useMarketplaceData } from "@/lib/marketplace";
import { usePageTitle } from "@/lib/seo";

export const forInstallersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/for-installers",
  validateSearch: (search: Record<string, unknown>) => ({
    listing: (search.listing as string) || "",
    state: (search.state as string) || "",
  }),
  component: ForInstallersPage,
});

function ForInstallersPage() {
  const search = forInstallersRoute.useSearch();
  const { data } = useMarketplaceData();
  const launchState = data?.launch_state || search.state || "our launch market";
  const featuredPlan = data?.plans.find((plan) => plan.is_featured);
  const freePlan = data?.plans.find((plan) => !plan.is_featured);

  usePageTitle(`For Installers in ${launchState}`);

  const [form, setForm] = useState({
    name: "",
    business_name: "",
    email: "",
    phone: "",
    state: search.state || data?.launch_state || "",
    notes: search.listing ? `I want to claim the profile for ${search.listing}.` : "",
  });
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    if (data?.launch_state && !form.state) {
      setForm((prev) => ({ ...prev, state: data.launch_state }));
    }
  }, [data?.launch_state, form.state]);

  const featureList = useMemo(
    () => featuredPlan?.features ?? [
      "Verified featured badge",
      "Top placement in the launch state",
      "Public phone and website",
      "Monthly performance summary",
    ],
    [featuredPlan]
  );

  const interestMutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.post("/installer-interest", {
        ...payload,
        listing_slug: search.listing || undefined,
        source_path: `${window.location.pathname}${window.location.search}`,
        ...getUtmPayload(window.location.search),
      }),
    onSuccess: () => setSubmitted(true),
  });

  return (
    <div className="max-w-6xl mx-auto px-4 py-12 space-y-10">
      <section className="grid lg:grid-cols-[1.2fr,0.8fr] gap-8 items-start">
        <div className="bg-primary text-primary-foreground rounded-3xl p-8 md:p-10">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide mb-4">
            Launch Offer
          </div>
          <h1 className="font-heading text-4xl md:text-5xl font-bold mb-4">
            Get Featured in {launchState}
          </h1>
          <p className="text-slate-200 text-lg mb-6 max-w-2xl">
            We are selling the first featured installer spots manually. The goal is simple:
            help serious local companies stand out, capture homeowner demand, and prove the
            market before we automate anything.
          </p>
          <div className="grid sm:grid-cols-2 gap-4">
            <div className="rounded-2xl bg-white/10 p-5">
              <div className="text-sm uppercase tracking-wide text-slate-300 mb-2">Free Profile</div>
              <div className="text-2xl font-bold mb-2">{freePlan ? "$0" : "$0"}</div>
              <p className="text-sm text-slate-200">
                Claimable directory presence, quote form, and basic visibility.
              </p>
            </div>
            <div className="rounded-2xl bg-accent text-accent-foreground p-5">
              <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide mb-2">
                <BadgeCheck className="w-4 h-4" /> Verified Featured
              </div>
              <div className="text-2xl font-bold mb-2">
                {featuredPlan ? `$${(featuredPlan.price_cents / 100).toFixed(0)}/mo` : "$99/mo"}
              </div>
              <p className="text-sm">
                Founding rate for the first 10 installers in {launchState}.
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl border border-border p-6 md:p-8">
          <h2 className="font-heading text-2xl font-bold mb-2">
            {search.listing ? "Claim This Profile" : "Request Featured Placement"}
          </h2>
          <p className="text-sm text-muted-foreground mb-6">
            Tell us about your company. We will verify the business, review the matching
            profile, and handle onboarding manually.
          </p>

          {submitted ? (
            <div className="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-900">
              <div className="flex items-center gap-2 font-semibold mb-2">
                <CheckCircle2 className="w-5 h-5" /> Inquiry received
              </div>
              <p className="text-sm">
                We will follow up with next steps for verification and featured placement.
              </p>
            </div>
          ) : (
            <form
              onSubmit={(e) => {
                e.preventDefault();
                interestMutation.mutate(form);
              }}
              className="space-y-4"
            >
              <div className="grid sm:grid-cols-2 gap-4">
                <label className="block text-sm">
                  <span className="font-medium">Your Name</span>
                  <input
                    value={form.name}
                    onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                    className="mt-1 w-full rounded-xl border border-border bg-background px-3 py-2"
                    required
                  />
                </label>
                <label className="block text-sm">
                  <span className="font-medium">Business Name</span>
                  <input
                    value={form.business_name}
                    onChange={(e) => setForm((prev) => ({ ...prev, business_name: e.target.value }))}
                    className="mt-1 w-full rounded-xl border border-border bg-background px-3 py-2"
                    required
                  />
                </label>
              </div>

              <div className="grid sm:grid-cols-2 gap-4">
                <label className="block text-sm">
                  <span className="font-medium">Email</span>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
                    className="mt-1 w-full rounded-xl border border-border bg-background px-3 py-2"
                    required
                  />
                </label>
                <label className="block text-sm">
                  <span className="font-medium">Phone</span>
                  <input
                    type="tel"
                    value={form.phone}
                    onChange={(e) => setForm((prev) => ({ ...prev, phone: e.target.value }))}
                    className="mt-1 w-full rounded-xl border border-border bg-background px-3 py-2"
                  />
                </label>
              </div>

              <label className="block text-sm">
                <span className="font-medium">State</span>
                <input
                  value={form.state}
                  onChange={(e) => setForm((prev) => ({ ...prev, state: e.target.value }))}
                  className="mt-1 w-full rounded-xl border border-border bg-background px-3 py-2"
                  required
                />
              </label>

              <label className="block text-sm">
                <span className="font-medium">Notes</span>
                <textarea
                  value={form.notes}
                  onChange={(e) => setForm((prev) => ({ ...prev, notes: e.target.value }))}
                  className="mt-1 w-full min-h-[120px] rounded-xl border border-border bg-background px-3 py-2"
                  placeholder="Tell us which profile is yours, what services you offer, and how quickly you want to launch."
                />
              </label>

              {interestMutation.isError && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {(interestMutation.error as Error)?.message || "Failed to submit your request."}
                </div>
              )}

              <button
                type="submit"
                disabled={interestMutation.isPending}
                className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-3 font-semibold text-accent-foreground transition-colors hover:bg-accent/90 disabled:opacity-50"
              >
                {interestMutation.isPending ? "Submitting..." : "Request Featured Placement"}
                <ArrowRight className="w-4 h-4" />
              </button>
            </form>
          )}
        </div>
      </section>

      <section className="grid md:grid-cols-[1fr,0.9fr] gap-8">
        <div className="bg-white rounded-3xl border border-border p-8">
          <h2 className="font-heading text-2xl font-bold mb-4">What you get on the featured plan</h2>
          <div className="space-y-3">
            {featureList.map((feature) => (
              <div key={feature} className="flex items-start gap-3 text-sm text-foreground">
                <CheckCircle2 className="w-4 h-4 text-accent mt-0.5 shrink-0" />
                <span>{feature}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-3xl border border-border p-8">
          <h2 className="font-heading text-2xl font-bold mb-4">Manual onboarding</h2>
          <div className="space-y-4 text-sm text-muted-foreground">
            <p>
              1. You send us your company details or the profile you want to claim.
            </p>
            <p>
              2. We verify the business and clean up the profile by hand.
            </p>
            <p>
              3. If it is a fit, we activate the featured listing and track proof in your dashboard.
            </p>
          </div>
          <div className="mt-6 rounded-2xl bg-muted p-4 text-sm">
            <div className="flex items-center gap-2 mb-2 font-medium text-foreground">
              <Mail className="w-4 h-4 text-accent" /> Questions
            </div>
            <p className="text-muted-foreground">{data?.contact_email || "info@findsolarinstallers.xyz"}</p>
            <div className="flex items-center gap-2 mt-4 mb-2 font-medium text-foreground">
              <Phone className="w-4 h-4 text-accent" /> Founder-led sales
            </div>
            <p className="text-muted-foreground">
              We are closing the first installers directly. There is no self-serve Stripe flow in this phase.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}
