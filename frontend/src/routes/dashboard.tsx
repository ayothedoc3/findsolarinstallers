import { createRoute, Outlet, Link } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "@/lib/api";
import {
  LayoutDashboard, List, Inbox, User, Plus, Trash2, Eye, Mail, Phone, Save, X, Lock, Unlock,
} from "lucide-react";
import { useState, useEffect } from "react";

// ─── Dashboard layout ────────────────────────────────────────────────────────

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

// ─── Overview ────────────────────────────────────────────────────────────────

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
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Active Listings</div>
          <div className="text-2xl font-bold font-heading">{stats?.active_listings ?? "..."}</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Total Leads</div>
          <div className="text-2xl font-bold font-heading">{stats?.total_leads ?? "..."}</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Unread Leads</div>
          <div className="text-2xl font-bold font-heading">{stats?.unread_leads ?? "..."}</div>
        </div>
      </div>
      <div className="bg-white rounded-xl border border-border p-8 text-center">
        <h2 className="font-heading text-xl font-semibold mb-2">Get Started</h2>
        <p className="text-muted-foreground mb-4">Create your first listing to start receiving leads.</p>
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

// ─── My Listings ─────────────────────────────────────────────────────────────

export const dashboardListingsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/listings",
  component: DashboardListings,
});

const SERVICES = ["Residential Solar", "Commercial Solar", "Solar Maintenance", "Battery Storage", "Solar Pool Heating", "EV Charger + Solar"];

function DashboardListings() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({
    name: "", description: "", phone: "", email: "", website: "",
    address: "", city: "", state: "", zip_code: "",
    services_offered: [] as string[],
    financing_available: false, free_consultation: false,
    years_in_business: "",
    category_ids: [1],
  });

  const { data: listings = [] } = useQuery<any[]>({
    queryKey: ["dashboard", "listings"],
    queryFn: () => api.get("/dashboard/listings"),
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post("/dashboard/listings", data),
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
    setForm({ name: "", description: "", phone: "", email: "", website: "", address: "", city: "", state: "", zip_code: "", services_offered: [], financing_available: false, free_consultation: false, years_in_business: "", category_ids: [1] });
  }

  function toggleService(s: string) {
    setForm((p) => ({
      ...p,
      services_offered: p.services_offered.includes(s) ? p.services_offered.filter((x) => x !== s) : [...p.services_offered, s],
    }));
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">My Listings</h1>
        <button onClick={() => setShowForm(!showForm)} className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <Plus className="w-4 h-4" /> New Listing
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl border border-border p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold">Create New Listing</h3>
            <button onClick={() => setShowForm(false)} className="text-muted-foreground hover:text-foreground"><X className="w-4 h-4" /></button>
          </div>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              createMutation.mutate({
                ...form,
                years_in_business: form.years_in_business ? parseInt(form.years_in_business) : null,
              });
            }}
            className="space-y-4"
          >
            <div className="grid sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Business Name *</label>
                <input type="text" value={form.name} onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" required />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Phone</label>
                <input type="tel" value={form.phone} onChange={(e) => setForm((p) => ({ ...p, phone: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Email</label>
                <input type="email" value={form.email} onChange={(e) => setForm((p) => ({ ...p, email: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Website</label>
                <input type="url" value={form.website} onChange={(e) => setForm((p) => ({ ...p, website: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" placeholder="https://" />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Description</label>
              <textarea value={form.description} onChange={(e) => setForm((p) => ({ ...p, description: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" rows={3} />
            </div>

            <div className="grid sm:grid-cols-4 gap-4">
              <div className="sm:col-span-2">
                <label className="block text-sm font-medium mb-1">Address</label>
                <input type="text" value={form.address} onChange={(e) => setForm((p) => ({ ...p, address: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">City</label>
                <input type="text" value={form.city} onChange={(e) => setForm((p) => ({ ...p, city: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">State</label>
                <input type="text" value={form.state} onChange={(e) => setForm((p) => ({ ...p, state: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" maxLength={2} placeholder="CA" />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-2">Services Offered</label>
              <div className="flex flex-wrap gap-2">
                {SERVICES.map((s) => (
                  <button key={s} type="button" onClick={() => toggleService(s)} className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${form.services_offered.includes(s) ? "bg-accent text-accent-foreground border-accent" : "bg-white text-muted-foreground border-border hover:border-foreground"}`}>
                    {s}
                  </button>
                ))}
              </div>
            </div>

            <div className="grid sm:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Years in Business</label>
                <input type="number" value={form.years_in_business} onChange={(e) => setForm((p) => ({ ...p, years_in_business: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
              </div>
              <label className="flex items-center gap-2 text-sm pt-6">
                <input type="checkbox" checked={form.financing_available} onChange={(e) => setForm((p) => ({ ...p, financing_available: e.target.checked }))} />
                Financing Available
              </label>
              <label className="flex items-center gap-2 text-sm pt-6">
                <input type="checkbox" checked={form.free_consultation} onChange={(e) => setForm((p) => ({ ...p, free_consultation: e.target.checked }))} />
                Free Consultation
              </label>
            </div>

            <div className="flex gap-3 pt-2">
              <button type="submit" disabled={createMutation.isPending} className="bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors disabled:opacity-50">
                {createMutation.isPending ? "Creating..." : "Create Listing"}
              </button>
              <button type="button" onClick={() => setShowForm(false)} className="text-sm text-muted-foreground hover:text-foreground px-4 py-2">Cancel</button>
            </div>
          </form>
        </div>
      )}

      {listings.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          You don't have any listings yet. Create one to get started.
        </div>
      ) : (
        <div className="space-y-3">
          {listings.map((l: any) => (
            <div key={l.id} className="bg-white rounded-xl border border-border p-4 flex items-center justify-between">
              <div>
                <div className="font-medium">{l.name}</div>
                <div className="text-sm text-muted-foreground">
                  {[l.city, l.state].filter(Boolean).join(", ")} &middot; {l.status}
                  {l.services_offered?.length > 0 && ` · ${l.services_offered.length} services`}
                </div>
              </div>
              <div className="flex items-center gap-2">
                <a href={`/listing/${l.slug}`} className="text-muted-foreground hover:text-foreground p-2" title="View">
                  <Eye className="w-4 h-4" />
                </a>
                <button
                  onClick={() => { if (confirm("Delete this listing?")) deleteMutation.mutate(l.id); }}
                  className="text-destructive hover:text-destructive/80 p-2" title="Delete"
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

// ─── Leads ───────────────────────────────────────────────────────────────────

export const dashboardLeadsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/leads",
  component: DashboardLeads,
});

function DashboardLeads() {
  const queryClient = useQueryClient();
  const [unlockingId, setUnlockingId] = useState<number | null>(null);
  const [successMsg, setSuccessMsg] = useState("");

  // Handle Stripe return URL
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("payment") === "success") {
      setSuccessMsg("Lead unlocked successfully! Contact details are now visible.");
      queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      // Clean up URL
      window.history.replaceState({}, "", "/dashboard/leads");
      setTimeout(() => setSuccessMsg(""), 5000);
    }
  }, [queryClient]);

  const { data: leads = [] } = useQuery<any[]>({
    queryKey: ["dashboard", "leads"],
    queryFn: () => api.get("/dashboard/leads"),
  });

  const markReadMutation = useMutation({
    mutationFn: (id: number) => api.put(`/dashboard/leads/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["dashboard"] }),
  });

  const unlockMutation = useMutation({
    mutationFn: (leadId: number) => api.post<{ checkout_url: string }>("/stripe/checkout", { lead_id: leadId }),
    onSuccess: (data) => {
      if (data.checkout_url) {
        window.location.href = data.checkout_url;
      }
    },
    onError: (err: Error) => {
      setUnlockingId(null);
      alert(err.message || "Failed to start checkout");
    },
  });

  function handleUnlock(leadId: number) {
    setUnlockingId(leadId);
    unlockMutation.mutate(leadId);
  }

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Leads</h1>

      {successMsg && (
        <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
          <Unlock className="w-4 h-4" />
          {successMsg}
        </div>
      )}

      {leads.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          No leads yet. Leads will appear here when customers contact you through your listings.
        </div>
      ) : (
        <div className="space-y-3">
          {leads.map((lead: any) => (
            <div key={lead.id} className={`bg-white rounded-xl border p-4 ${lead.is_read ? "border-border" : "border-accent"}`}>
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <span className="font-medium">{lead.name}</span>
                    {!lead.is_read && <span className="px-2 py-0.5 rounded-full bg-accent/10 text-accent text-xs font-medium">New</span>}
                    {lead.is_unlocked ? (
                      <span className="px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-medium flex items-center gap-1"><Unlock className="w-3 h-3" /> Unlocked</span>
                    ) : (
                      <span className="px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 text-xs font-medium flex items-center gap-1"><Lock className="w-3 h-3" /> Locked</span>
                    )}
                  </div>

                  {lead.is_unlocked ? (
                    <>
                      <div className="flex items-center gap-3 text-sm text-muted-foreground mb-2">
                        <span className="flex items-center gap-1"><Mail className="w-3.5 h-3.5" /> {lead.email}</span>
                        {lead.phone && <span className="flex items-center gap-1"><Phone className="w-3.5 h-3.5" /> {lead.phone}</span>}
                      </div>
                      {lead.message && <p className="text-sm text-foreground">{lead.message}</p>}
                    </>
                  ) : (
                    <div className="mt-2 mb-2">
                      <div className="flex items-center gap-3 text-sm text-muted-foreground mb-2">
                        <span className="flex items-center gap-1 blur-sm select-none"><Mail className="w-3.5 h-3.5" /> hidden@email.com</span>
                        <span className="flex items-center gap-1 blur-sm select-none"><Phone className="w-3.5 h-3.5" /> (555) 000-0000</span>
                      </div>
                      <p className="text-sm text-muted-foreground blur-sm select-none">Message details are hidden until you unlock this lead...</p>
                    </div>
                  )}

                  <div className="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                    {lead.project_type && <span>Project: {lead.project_type}</span>}
                    {lead.zip_code && <span>ZIP: {lead.zip_code}</span>}
                    <span>{new Date(lead.created_at).toLocaleDateString()}</span>
                  </div>
                </div>

                <div className="flex flex-col items-end gap-2 ml-4">
                  {!lead.is_unlocked && (
                    <button
                      onClick={() => handleUnlock(lead.id)}
                      disabled={unlockingId === lead.id}
                      className="flex items-center gap-1.5 bg-accent hover:bg-accent/90 text-accent-foreground text-xs font-semibold px-3 py-2 rounded-lg transition-colors disabled:opacity-50 whitespace-nowrap"
                    >
                      <Lock className="w-3.5 h-3.5" />
                      {unlockingId === lead.id ? "Redirecting..." : "Unlock — $19.99"}
                    </button>
                  )}
                  {!lead.is_read && (
                    <button onClick={() => markReadMutation.mutate(lead.id)} className="text-xs text-muted-foreground hover:text-foreground border border-border rounded px-2 py-1">
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

// ─── Profile ─────────────────────────────────────────────────────────────────

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
          <button onClick={() => saveMutation.mutate(edits)} className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <Save className="w-4 h-4" /> Save Changes
          </button>
        )}
      </div>

      <div className="bg-white rounded-xl border border-border p-6 space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Email</label>
          <input type="email" value={profile.email} disabled className="w-full px-3 py-2 rounded-lg border border-border bg-muted text-sm text-muted-foreground" />
          <p className="text-xs text-muted-foreground mt-1">Email cannot be changed.</p>
        </div>
        {fields.map((f) => (
          <div key={f.key}>
            <label className="block text-sm font-medium mb-1">{f.label}</label>
            <input
              type="text"
              value={edits[f.key] ?? profile[f.key] ?? ""}
              onChange={(e) => setEdits((p) => ({ ...p, [f.key]: e.target.value }))}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
            />
          </div>
        ))}
      </div>
    </div>
  );
}
