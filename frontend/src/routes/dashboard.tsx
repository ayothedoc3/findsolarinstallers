import { createRoute, Link, Outlet } from "@tanstack/react-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Eye,
  Inbox,
  LayoutDashboard,
  List,
  Lock,
  Mail,
  Phone,
  Plus,
  Save,
  Trash2,
  User,
  X,
} from "lucide-react";
import { type ReactNode, useState } from "react";

import { api } from "@/lib/api";
import { rootRoute } from "./__root";

export const dashboardLayoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/dashboard",
  component: DashboardLayout,
});

function DashboardLayout() {
  const navItems = [
    { to: "/dashboard", icon: LayoutDashboard, label: "Overview" },
    { to: "/dashboard/listings", icon: List, label: "My Listings" },
    { to: "/dashboard/leads", icon: Inbox, label: "Leads" },
    { to: "/dashboard/profile", icon: User, label: "Profile" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="grid lg:grid-cols-5 gap-8">
        <aside className="lg:col-span-1">
          <nav className="bg-white rounded-xl border border-border p-2 space-y-1 sticky top-20">
            <div className="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Dashboard
            </div>
            {navItems.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors [&.active]:bg-accent/10 [&.active]:text-accent"
                activeOptions={{ exact: item.to === "/dashboard" }}
              >
                <item.icon className="w-4 h-4" />
                {item.label}
              </Link>
            ))}
          </nav>
        </aside>
        <section className="lg:col-span-4">
          <Outlet />
        </section>
      </div>
    </div>
  );
}

export const dashboardIndexRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/",
  component: DashboardOverview,
});

function DashboardOverview() {
  const { data: stats } = useQuery({
    queryKey: ["dashboard", "stats"],
    queryFn: () => api.get<{ active_listings: number; total_leads: number; unread_leads: number }>("/dashboard/stats"),
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Dashboard</h1>
      <div className="grid sm:grid-cols-3 gap-4 mb-8">
        <StatCard label="Active Listings" value={stats?.active_listings} />
        <StatCard label="Total Leads" value={stats?.total_leads} />
        <StatCard label="Unread Leads" value={stats?.unread_leads} />
      </div>
      <div className="bg-white rounded-xl border border-border p-8 text-center">
        <h2 className="font-heading text-xl font-semibold mb-2">Get Started</h2>
        <p className="text-muted-foreground mb-4">
          Create or claim a profile, then wait for approval before it goes live.
        </p>
        <Link
          to="/dashboard/listings"
          className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground font-semibold px-6 py-3 rounded-lg transition-colors"
        >
          <List className="w-4 h-4" /> Manage Listings
        </Link>
      </div>
    </div>
  );
}

function StatCard({ label, value }: { label: string; value: number | undefined }) {
  return (
    <div className="bg-white rounded-xl border border-border p-5">
      <div className="text-sm text-muted-foreground mb-1">{label}</div>
      <div className="text-2xl font-bold font-heading">{value ?? "..."}</div>
    </div>
  );
}

export const dashboardListingsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/listings",
  component: DashboardListings,
});

const SERVICES = [
  "Residential Solar",
  "Commercial Solar",
  "Solar Maintenance",
  "Battery Storage",
  "Solar Pool Heating",
  "EV Charger + Solar",
];

interface DashboardListing {
  id: number;
  name: string;
  slug: string;
  city: string | null;
  state: string | null;
  status: string;
  services_offered: string[];
  plan_name?: string | null;
  is_featured?: boolean;
  views_30d?: number;
  quote_requests_30d?: number;
  expires_at?: string | null;
}

function DashboardListings() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({
    name: "",
    description: "",
    phone: "",
    email: "",
    website: "",
    address: "",
    city: "",
    state: "",
    zip_code: "",
    services_offered: [] as string[],
    financing_available: false,
    free_consultation: false,
    years_in_business: "",
    category_ids: [1],
  });

  const { data: listings = [] } = useQuery<DashboardListing[]>({
    queryKey: ["dashboard", "listings"],
    queryFn: () => api.get("/dashboard/listings"),
  });

  const createMutation = useMutation({
    mutationFn: (data: unknown) => api.post("/dashboard/listings", data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      setShowForm(false);
      resetForm();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/dashboard/listings/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["dashboard"] }),
  });

  function resetForm() {
    setForm({
      name: "",
      description: "",
      phone: "",
      email: "",
      website: "",
      address: "",
      city: "",
      state: "",
      zip_code: "",
      services_offered: [],
      financing_available: false,
      free_consultation: false,
      years_in_business: "",
      category_ids: [1],
    });
  }

  function toggleService(service: string) {
    setForm((prev) => ({
      ...prev,
      services_offered: prev.services_offered.includes(service)
        ? prev.services_offered.filter((item) => item !== service)
        : [...prev.services_offered, service],
    }));
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">My Listings</h1>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
        >
          <Plus className="w-4 h-4" /> New Listing
        </button>
      </div>

      <div className="bg-white rounded-xl border border-border p-4 mb-6 text-sm text-muted-foreground">
        New self-serve profiles are submitted as <span className="font-medium text-foreground">pending review</span>.
        Verified Featured profiles get direct contact visibility and full lead access.
      </div>

      {showForm && (
        <div className="bg-white rounded-xl border border-border p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold">Create New Listing</h3>
            <button onClick={() => setShowForm(false)} className="text-muted-foreground hover:text-foreground">
              <X className="w-4 h-4" />
            </button>
          </div>

          <form
            onSubmit={(e) => {
              e.preventDefault();
              createMutation.mutate({
                ...form,
                years_in_business: form.years_in_business ? parseInt(form.years_in_business, 10) : null,
              });
            }}
            className="space-y-4"
          >
            <div className="grid sm:grid-cols-2 gap-4">
              <Field label="Business Name *">
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                  required
                />
              </Field>
              <Field label="Phone">
                <input
                  type="tel"
                  value={form.phone}
                  onChange={(e) => setForm((prev) => ({ ...prev, phone: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
              <Field label="Email">
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
              <Field label="Website">
                <input
                  type="url"
                  value={form.website}
                  onChange={(e) => setForm((prev) => ({ ...prev, website: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                  placeholder="https://"
                />
              </Field>
            </div>

            <Field label="Description">
              <textarea
                value={form.description}
                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                rows={3}
              />
            </Field>

            <div className="grid sm:grid-cols-4 gap-4">
              <Field label="Address" className="sm:col-span-2">
                <input
                  type="text"
                  value={form.address}
                  onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
              <Field label="City">
                <input
                  type="text"
                  value={form.city}
                  onChange={(e) => setForm((prev) => ({ ...prev, city: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
              <Field label="State">
                <input
                  type="text"
                  value={form.state}
                  onChange={(e) => setForm((prev) => ({ ...prev, state: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
            </div>

            <div>
              <label className="block text-sm font-medium mb-2">Services Offered</label>
              <div className="flex flex-wrap gap-2">
                {SERVICES.map((service) => (
                  <button
                    key={service}
                    type="button"
                    onClick={() => toggleService(service)}
                    className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${
                      form.services_offered.includes(service)
                        ? "bg-accent text-accent-foreground border-accent"
                        : "bg-white text-muted-foreground border-border hover:border-foreground"
                    }`}
                  >
                    {service}
                  </button>
                ))}
              </div>
            </div>

            <div className="grid sm:grid-cols-3 gap-4">
              <Field label="Years in Business">
                <input
                  type="number"
                  value={form.years_in_business}
                  onChange={(e) => setForm((prev) => ({ ...prev, years_in_business: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                />
              </Field>
              <label className="flex items-center gap-2 text-sm pt-6">
                <input
                  type="checkbox"
                  checked={form.financing_available}
                  onChange={(e) => setForm((prev) => ({ ...prev, financing_available: e.target.checked }))}
                />
                Financing Available
              </label>
              <label className="flex items-center gap-2 text-sm pt-6">
                <input
                  type="checkbox"
                  checked={form.free_consultation}
                  onChange={(e) => setForm((prev) => ({ ...prev, free_consultation: e.target.checked }))}
                />
                Free Consultation
              </label>
            </div>

            <div className="flex gap-3 pt-2">
              <button
                type="submit"
                disabled={createMutation.isPending}
                className="bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors disabled:opacity-50"
              >
                {createMutation.isPending ? "Submitting..." : "Submit for Review"}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="text-sm text-muted-foreground hover:text-foreground px-4 py-2"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {listings.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          You do not have any profiles yet. Create one to get started.
        </div>
      ) : (
        <div className="space-y-3">
          {listings.map((listing) => (
            <div key={listing.id} className="bg-white rounded-xl border border-border p-4 flex items-center justify-between">
              <div>
                <div className="flex items-center gap-2 flex-wrap">
                  <div className="font-medium">{listing.name}</div>
                  <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                    listing.is_featured ? "bg-accent/10 text-accent" : "bg-muted text-muted-foreground"
                  }`}>
                    {listing.plan_name || "Free Profile"}
                  </span>
                  <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                    listing.status === "active"
                      ? "bg-green-50 text-green-700"
                      : listing.status === "pending_review"
                        ? "bg-orange-50 text-orange-700"
                        : "bg-muted text-muted-foreground"
                  }`}>
                    {listing.status.replace("_", " ")}
                  </span>
                </div>
                <div className="text-sm text-muted-foreground">
                  {[listing.city, listing.state].filter(Boolean).join(", ") || "Location pending"}
                  {listing.services_offered?.length > 0 && ` · ${listing.services_offered.length} services`}
                </div>
                <div className="flex flex-wrap gap-3 text-xs text-muted-foreground mt-2">
                  <span>{listing.views_30d ?? 0} profile views (30d)</span>
                  <span>{listing.quote_requests_30d ?? 0} quote requests (30d)</span>
                  {listing.expires_at && <span>Expires {new Date(listing.expires_at).toLocaleDateString()}</span>}
                </div>
              </div>
              <div className="flex items-center gap-2">
                <a href={`/listing/${listing.slug}`} className="text-muted-foreground hover:text-foreground p-2" title="View">
                  <Eye className="w-4 h-4" />
                </a>
                <button
                  onClick={() => {
                    if (confirm("Delete this listing?")) deleteMutation.mutate(listing.id);
                  }}
                  className="text-destructive hover:text-destructive/80 p-2"
                  title="Delete"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function Field({
  children,
  className = "",
  label,
}: {
  children: ReactNode;
  className?: string;
  label: string;
}) {
  return (
    <div className={className}>
      <label className="block text-sm font-medium mb-1">{label}</label>
      {children}
    </div>
  );
}

export const dashboardLeadsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/leads",
  component: DashboardLeads,
});

function DashboardLeads() {
  const queryClient = useQueryClient();

  const { data: leads = [] } = useQuery<any[]>({
    queryKey: ["dashboard", "leads"],
    queryFn: () => api.get("/dashboard/leads"),
  });

  const markReadMutation = useMutation({
    mutationFn: (id: number) => api.put(`/dashboard/leads/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["dashboard"] }),
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Leads</h1>
      <div className="bg-white rounded-xl border border-border p-4 mb-4 text-sm text-muted-foreground">
        Pay-per-lead checkout is de-emphasized in this phase. Verified Featured profiles see
        full homeowner details automatically; basic profiles only see proof that demand exists.
      </div>

      {leads.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          No leads yet. Quote requests will appear here when homeowners contact your profile.
        </div>
      ) : (
        <div className="space-y-3">
          {leads.map((lead) => (
            <div key={lead.id} className={`bg-white rounded-xl border p-4 ${lead.is_read ? "border-border" : "border-accent"}`}>
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1 flex-wrap">
                    {lead.listing_name && (
                      <span className="text-xs text-muted-foreground">{lead.listing_name}</span>
                    )}
                    <span className="font-medium">{lead.name}</span>
                    {!lead.is_read && (
                      <span className="px-2 py-0.5 rounded-full bg-accent/10 text-accent text-xs font-medium">
                        New
                      </span>
                    )}
                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                      lead.is_unlocked ? "bg-green-50 text-green-700" : "bg-orange-50 text-orange-700"
                    }`}>
                      {lead.is_unlocked ? "Visible" : "Locked"}
                    </span>
                  </div>

                  {lead.is_unlocked ? (
                    <>
                      <div className="flex items-center gap-3 text-sm text-muted-foreground mb-2 flex-wrap">
                        {lead.email && (
                          <span className="flex items-center gap-1">
                            <Mail className="w-3.5 h-3.5" /> {lead.email}
                          </span>
                        )}
                        {lead.phone && (
                          <span className="flex items-center gap-1">
                            <Phone className="w-3.5 h-3.5" /> {lead.phone}
                          </span>
                        )}
                      </div>
                      {lead.message && <p className="text-sm text-foreground">{lead.message}</p>}
                    </>
                  ) : (
                    <div className="mt-2 mb-2">
                      <div className="flex items-center gap-3 text-sm text-muted-foreground mb-2">
                        <span className="flex items-center gap-1 blur-sm select-none">
                          <Mail className="w-3.5 h-3.5" /> hidden@email.com
                        </span>
                        <span className="flex items-center gap-1 blur-sm select-none">
                          <Phone className="w-3.5 h-3.5" /> (555) 000-0000
                        </span>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        Full contact details are only visible on Verified Featured profiles during this launch phase.
                      </p>
                    </div>
                  )}

                  <div className="flex items-center gap-3 mt-2 text-xs text-muted-foreground flex-wrap">
                    {lead.project_type && <span>Project: {lead.project_type}</span>}
                    {lead.zip_code && <span>ZIP: {lead.zip_code}</span>}
                    <span>{new Date(lead.created_at).toLocaleDateString()}</span>
                  </div>
                </div>

                <div className="flex flex-col items-end gap-2 ml-4">
                  {!lead.is_unlocked && lead.requires_featured_upgrade && (
                    <a href="/for-installers" className="text-xs font-semibold text-accent hover:text-accent/80 whitespace-nowrap">
                      Upgrade to Verified Featured
                    </a>
                  )}
                  {!lead.is_read && (
                    <button
                      onClick={() => markReadMutation.mutate(lead.id)}
                      className="text-xs text-muted-foreground hover:text-foreground border border-border rounded px-2 py-1"
                    >
                      Mark Read
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export const dashboardProfileRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/profile",
  component: DashboardProfile,
});

function DashboardProfile() {
  const queryClient = useQueryClient();
  const [edits, setEdits] = useState<Record<string, string>>({});

  const { data: profile } = useQuery<any>({
    queryKey: ["dashboard", "profile"],
    queryFn: () => api.get("/dashboard/profile"),
  });

  const saveMutation = useMutation({
    mutationFn: (data: Record<string, string>) => api.put("/dashboard/profile", data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["dashboard", "profile"] });
      setEdits({});
    },
  });

  if (!profile) return <div className="text-muted-foreground">Loading...</div>;

  const fields = [
    { key: "first_name", label: "First Name" },
    { key: "last_name", label: "Last Name" },
    { key: "company_name", label: "Company Name" },
    { key: "phone", label: "Phone" },
  ];

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Profile Settings</h1>
        {Object.keys(edits).length > 0 && (
          <button
            onClick={() => saveMutation.mutate(edits)}
            className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
          >
            <Save className="w-4 h-4" /> Save Changes
          </button>
        )}
      </div>

      <div className="bg-white rounded-xl border border-border p-6 space-y-4">
        <Field label="Email">
          <input
            type="email"
            value={profile.email}
            disabled
            className="w-full px-3 py-2 rounded-lg border border-border bg-muted text-sm text-muted-foreground"
          />
        </Field>
        {fields.map((field) => (
          <Field key={field.key} label={field.label}>
            <input
              type="text"
              value={edits[field.key] ?? profile[field.key] ?? ""}
              onChange={(e) => setEdits((prev) => ({ ...prev, [field.key]: e.target.value }))}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
            />
          </Field>
        ))}
      </div>
    </div>
  );
}
