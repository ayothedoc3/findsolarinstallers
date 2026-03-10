import { createRoute, Outlet, Link } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "@/lib/api";
import {
  LayoutDashboard, List, Users, Key, Workflow, Settings, Tag, CreditCard,
  Plus, Trash2, Star, Eye, Play, MapPin, Shield, ShieldOff, Save, XCircle,
  Activity, UserCheck, Check, X, BarChart3, Inbox,
} from "lucide-react";
import { useState } from "react";

// ─── Admin layout ────────────────────────────────────────────────────────────

export const adminLayoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/admin",
  component: AdminLayout,
});

function AdminLayout() {
  const navItems = [
    { to: "/admin", icon: LayoutDashboard, label: "Dashboard" },
    { to: "/admin/listings", icon: List, label: "Listings" },
    { to: "/admin/users", icon: Users, label: "Users" },
    { to: "/admin/categories", icon: Tag, label: "Categories" },
    { to: "/admin/plans", icon: CreditCard, label: "Plans" },
    { to: "/admin/api-keys", icon: Key, label: "API Keys" },
    { to: "/admin/claims", icon: UserCheck, label: "Claims" },
    { to: "/admin/installer-inquiries", icon: Inbox, label: "Installer Inquiries" },
    { to: "/admin/pipeline", icon: Workflow, label: "Pipeline" },
    { to: "/admin/analytics", icon: BarChart3, label: "Analytics" },
    { to: "/admin/settings", icon: Settings, label: "Settings" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="grid lg:grid-cols-5 gap-8">
        <aside className="lg:col-span-1">
          <nav className="bg-white rounded-xl border border-border p-2 space-y-1 sticky top-20">
            <div className="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Admin Panel
            </div>
            {navItems.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors [&.active]:bg-accent/10 [&.active]:text-accent"
                activeOptions={{ exact: item.to === "/admin" }}
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

// ─── Dashboard ───────────────────────────────────────────────────────────────

export const adminIndexRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/",
  component: AdminDashboard,
});

function AdminDashboard() {
  const { data: stats } = useQuery({
    queryKey: ["admin", "stats"],
    queryFn: () =>
      api.get<{
        total_listings: number;
        total_states: number;
        total_reviews: number;
        total_users: number;
        total_leads: number;
        recent_leads: number;
        total_lead_revenue_cents: number;
      }>("/admin/stats"),
  });

  const cards = [
    { label: "Total Listings", value: stats?.total_listings },
    { label: "States Covered", value: stats?.total_states },
    { label: "Total Reviews", value: stats?.total_reviews?.toLocaleString() },
    { label: "Users", value: stats?.total_users },
    { label: "Total Leads", value: stats?.total_leads },
    { label: "Leads (30d)", value: stats?.recent_leads },
  ];

  const revenue = stats?.total_lead_revenue_cents != null
    ? `$${(stats.total_lead_revenue_cents / 100).toFixed(2)}`
    : "...";

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Admin Dashboard</h1>

      {/* Revenue highlight */}
      <div className="bg-gradient-to-r from-primary to-primary/80 rounded-xl p-6 mb-6 text-primary-foreground">
        <div className="text-sm opacity-80 mb-1">Legacy Lead Unlock Revenue</div>
        <div className="text-3xl font-bold font-heading">{revenue}</div>
        <div className="text-sm opacity-70 mt-1">Kept for historical tracking while featured profiles become the main offer</div>
      </div>

      <div className="grid sm:grid-cols-3 gap-4 mb-8">
        {cards.map((c) => (
          <div key={c.label} className="bg-white rounded-xl border border-border p-5">
            <div className="text-sm text-muted-foreground mb-1">{c.label}</div>
            <div className="text-2xl font-bold font-heading">{c.value ?? "..."}</div>
          </div>
        ))}
      </div>
    </div>
  );
}

// ─── Listings ────────────────────────────────────────────────────────────────

export const adminListingsRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/listings",
  component: AdminListings,
});

function AdminListings() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [ownershipFilter, setOwnershipFilter] = useState("");
  const [assigningId, setAssigningId] = useState<number | null>(null);
  const [assignEmail, setAssignEmail] = useState("");

  const { data } = useQuery({
    queryKey: ["admin", "listings", search, statusFilter, ownershipFilter],
    queryFn: () => {
      const params = new URLSearchParams();
      if (search) params.set("q", search);
      if (statusFilter) params.set("status", statusFilter);
      if (ownershipFilter) params.set("ownership", ownershipFilter);
      params.set("per_page", "50");
      return api.get<{ items: any[]; total: number }>(`/admin/listings?${params}`);
    },
  });

  const { data: allUsers } = useQuery<{ items: any[] }>({
    queryKey: ["admin", "users-for-assign"],
    queryFn: () => api.get("/admin/users?per_page=200"),
  });

  const { data: plans = [] } = useQuery<any[]>({
    queryKey: ["admin", "plans-for-listings"],
    queryFn: () => api.get("/admin/plans"),
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/listings/${id}/status?status=${status}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "listings"] }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/listings/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "listings"] }),
  });

  const assignOwnerMutation = useMutation({
    mutationFn: ({ id, owner_id }: { id: number; owner_id: number | null }) =>
      api.put(`/admin/listings/${id}/owner`, { owner_id }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin", "listings"] });
      setAssigningId(null);
      setAssignEmail("");
    },
  });

  const planMutation = useMutation({
    mutationFn: ({ id, plan_id }: { id: number; plan_id: number }) =>
      api.put(`/admin/listings/${id}/plan`, { plan_id }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "listings"] }),
  });

  const unownedCount = data?.items?.filter((l: any) => !l.owner_id).length ?? 0;
  const freePlan = plans.find((plan: any) => !plan.is_featured);
  const featuredPlan = plans.find((plan: any) => plan.is_featured);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Manage Listings</h1>
        <div className="flex items-center gap-3">
          {unownedCount > 0 && (
            <span className="text-xs px-2.5 py-1 rounded-full bg-orange-50 text-orange-700 font-medium">
              {unownedCount} unowned
            </span>
          )}
          <span className="text-sm text-muted-foreground">{data?.total ?? 0} total</span>
        </div>
      </div>

      <div className="flex gap-3 mb-4">
        <input
          type="text"
          placeholder="Search listings..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="flex-1 px-3 py-2 rounded-lg border border-border bg-white text-sm"
        />
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
        >
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="pending_review">Pending Review</option>
          <option value="pending">Pending</option>
          <option value="suspended">Suspended</option>
          <option value="expired">Expired</option>
        </select>
        <select
          value={ownershipFilter}
          onChange={(e) => setOwnershipFilter(e.target.value)}
          className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
        >
          <option value="">All Ownership</option>
          <option value="unowned">Unowned (No Owner)</option>
          <option value="owned">Owned</option>
        </select>
      </div>

      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">Name</th>
              <th className="text-left px-4 py-3 font-medium">Location</th>
              <th className="text-left px-4 py-3 font-medium">Rating</th>
              <th className="text-left px-4 py-3 font-medium">Owner</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {!data?.items?.length ? (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                  No listings found.
                </td>
              </tr>
            ) : (
              data.items.map((l: any) => (
                <tr key={l.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium max-w-[220px]">
                    <div className="truncate">{l.name}</div>
                    <div className="mt-1">
                      <span className={`px-2 py-0.5 rounded-full text-[11px] font-medium ${
                        l.is_featured ? "bg-accent/10 text-accent" : "bg-muted text-muted-foreground"
                      }`}>
                        {l.plan_name || "Free Profile"}
                      </span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground text-xs">
                    {[l.city, l.state].filter(Boolean).join(", ") || "—"}
                  </td>
                  <td className="px-4 py-3">
                    {l.google_rating ? (
                      <span className="flex items-center gap-1">
                        <Star className="w-3.5 h-3.5 fill-accent text-accent" />
                        {l.google_rating}
                      </span>
                    ) : "—"}
                  </td>
                  <td className="px-4 py-3">
                    {assigningId === l.id ? (
                      <div className="flex items-center gap-1">
                        <select
                          value={assignEmail}
                          onChange={(e) => setAssignEmail(e.target.value)}
                          className="px-2 py-1 rounded border border-border text-xs bg-white max-w-[150px]"
                        >
                          <option value="">None</option>
                          {allUsers?.items?.map((u: any) => (
                            <option key={u.id} value={u.id}>{u.email}</option>
                          ))}
                        </select>
                        <button
                          onClick={() => assignOwnerMutation.mutate({ id: l.id, owner_id: assignEmail ? parseInt(assignEmail) : null })}
                          className="text-green-600 hover:text-green-800 p-0.5" title="Save"
                        >
                          <Check className="w-3.5 h-3.5" />
                        </button>
                        <button onClick={() => setAssigningId(null)} className="text-muted-foreground hover:text-foreground p-0.5" title="Cancel">
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    ) : (
                      <button
                        onClick={() => { setAssigningId(l.id); setAssignEmail(l.owner_id?.toString() || ""); }}
                        className={`text-xs px-2 py-1 rounded-full ${l.owner_email ? "bg-green-50 text-green-700 hover:bg-green-100" : "bg-orange-50 text-orange-700 hover:bg-orange-100"} transition-colors`}
                        title="Click to assign owner"
                      >
                        {l.owner_email || "Assign Owner"}
                      </button>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <select
                      value={l.status || "active"}
                      onChange={(e) => statusMutation.mutate({ id: l.id, status: e.target.value })}
                      className="px-2 py-1 rounded border border-border text-xs bg-white"
                    >
                      <option value="active">Active</option>
                      <option value="pending_review">Pending Review</option>
                      <option value="pending">Pending</option>
                      <option value="suspended">Suspended</option>
                      <option value="expired">Expired</option>
                    </select>
                    <div className="mt-2 text-[11px] text-muted-foreground">
                      {l.views_30d ?? 0} views / {l.quote_requests_30d ?? 0} quotes
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right flex justify-end gap-2">
                    {featuredPlan && !l.is_featured && (
                      <button
                        onClick={() => planMutation.mutate({ id: l.id, plan_id: featuredPlan.id })}
                        className="text-xs px-2 py-1 rounded border border-accent/30 text-accent hover:bg-accent/10"
                        title="Assign featured plan"
                      >
                        Feature 30d
                      </button>
                    )}
                    {freePlan && l.plan_id !== freePlan.id && (
                      <button
                        onClick={() => planMutation.mutate({ id: l.id, plan_id: freePlan.id })}
                        className="text-xs px-2 py-1 rounded border border-border text-muted-foreground hover:text-foreground"
                        title="Reset to free plan"
                      >
                        Set Free
                      </button>
                    )}
                    <a href={`/listing/${l.slug}`} className="text-muted-foreground hover:text-foreground p-1" title="View">
                      <Eye className="w-4 h-4" />
                    </a>
                    <button
                      onClick={() => { if (confirm("Delete this listing?")) deleteMutation.mutate(l.id); }}
                      className="text-destructive hover:text-destructive/80 p-1" title="Delete"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Users ───────────────────────────────────────────────────────────────────

export const adminUsersRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/users",
  component: AdminUsers,
});

function AdminUsers() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");

  const { data } = useQuery({
    queryKey: ["admin", "users", search],
    queryFn: () => {
      const params = new URLSearchParams();
      if (search) params.set("q", search);
      params.set("per_page", "50");
      return api.get<{ items: any[]; total: number }>(`/admin/users?${params}`);
    },
  });

  const roleMutation = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) =>
      api.put(`/admin/users/${id}/role?role=${role}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "users"] }),
  });

  const toggleMutation = useMutation({
    mutationFn: (id: number) => api.put(`/admin/users/${id}/toggle-active`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "users"] }),
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Manage Users</h1>
        <span className="text-sm text-muted-foreground">{data?.total ?? 0} total</span>
      </div>

      <div className="mb-4">
        <input
          type="text"
          placeholder="Search by email or name..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full px-3 py-2 rounded-lg border border-border bg-white text-sm"
        />
      </div>

      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">Email</th>
              <th className="text-left px-4 py-3 font-medium">Name</th>
              <th className="text-left px-4 py-3 font-medium">Role</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {!data?.items?.length ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">No users found.</td>
              </tr>
            ) : (
              data.items.map((u: any) => (
                <tr key={u.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">{u.email}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {[u.first_name, u.last_name].filter(Boolean).join(" ") || "—"}
                  </td>
                  <td className="px-4 py-3">
                    <select
                      value={u.role}
                      onChange={(e) => roleMutation.mutate({ id: u.id, role: e.target.value })}
                      className="px-2 py-1 rounded border border-border text-xs bg-white"
                    >
                      <option value="user">User</option>
                      <option value="business_owner">Business Owner</option>
                      <option value="admin">Admin</option>
                    </select>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${u.is_active ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"}`}>
                      {u.is_active ? "Active" : "Disabled"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => toggleMutation.mutate(u.id)}
                      className="text-muted-foreground hover:text-foreground p-1"
                      title={u.is_active ? "Disable user" : "Enable user"}
                    >
                      {u.is_active ? <ShieldOff className="w-4 h-4" /> : <Shield className="w-4 h-4" />}
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Categories ──────────────────────────────────────────────────────────────

export const adminCategoriesRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/categories",
  component: AdminCategories,
});

function AdminCategories() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState({ name: "", slug: "", parent_id: null as number | null, description: "", icon: "", sort_order: 0 });

  const { data: categories = [] } = useQuery<any[]>({
    queryKey: ["admin", "categories"],
    queryFn: () => api.get("/admin/categories"),
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post("/admin/categories", data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ["admin", "categories"] }); resetForm(); },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) => api.put(`/admin/categories/${id}`, data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ["admin", "categories"] }); resetForm(); },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/categories/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "categories"] }),
  });

  function resetForm() {
    setShowForm(false);
    setEditId(null);
    setForm({ name: "", slug: "", parent_id: null, description: "", icon: "", sort_order: 0 });
  }

  function startEdit(cat: any) {
    setEditId(cat.id);
    setForm({ name: cat.name, slug: cat.slug, parent_id: cat.parent_id, description: cat.description || "", icon: cat.icon || "", sort_order: cat.sort_order });
    setShowForm(true);
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Categories</h1>
        <button onClick={() => { resetForm(); setShowForm(!showForm); }} className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <Plus className="w-4 h-4" /> Add Category
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl border border-border p-6 mb-6">
          <h3 className="font-semibold mb-4">{editId ? "Edit Category" : "New Category"}</h3>
          <form onSubmit={(e) => { e.preventDefault(); editId ? updateMutation.mutate({ id: editId, data: form }) : createMutation.mutate(form); }} className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Name</label>
              <input type="text" value={form.name} onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" required />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Slug</label>
              <input type="text" value={form.slug} onChange={(e) => setForm((p) => ({ ...p, slug: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" required />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Icon (Lucide name)</label>
              <input type="text" value={form.icon} onChange={(e) => setForm((p) => ({ ...p, icon: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Sort Order</label>
              <input type="number" value={form.sort_order} onChange={(e) => setForm((p) => ({ ...p, sort_order: parseInt(e.target.value) || 0 }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium mb-1">Description</label>
              <textarea value={form.description} onChange={(e) => setForm((p) => ({ ...p, description: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" rows={2} />
            </div>
            <div className="sm:col-span-2 flex gap-3">
              <button type="submit" className="bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2 rounded-lg transition-colors">{editId ? "Update" : "Create"}</button>
              <button type="button" onClick={resetForm} className="text-sm text-muted-foreground hover:text-foreground px-4 py-2">Cancel</button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">Name</th>
              <th className="text-left px-4 py-3 font-medium">Slug</th>
              <th className="text-left px-4 py-3 font-medium">Icon</th>
              <th className="text-left px-4 py-3 font-medium">Listings</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {categories.map((cat: any) => (
              <tr key={cat.id} className="border-b border-border last:border-0">
                <td className="px-4 py-3 font-medium">
                  {cat.parent_id ? <span className="text-muted-foreground mr-2">└</span> : null}
                  {cat.name}
                </td>
                <td className="px-4 py-3 text-muted-foreground">{cat.slug}</td>
                <td className="px-4 py-3 text-muted-foreground">{cat.icon || "—"}</td>
                <td className="px-4 py-3">{cat.listing_count}</td>
                <td className="px-4 py-3 text-right flex justify-end gap-2">
                  <button onClick={() => startEdit(cat)} className="text-muted-foreground hover:text-foreground p-1" title="Edit">
                    <Settings className="w-4 h-4" />
                  </button>
                  <button onClick={() => { if (confirm("Delete this category?")) deleteMutation.mutate(cat.id); }} className="text-destructive hover:text-destructive/80 p-1" title="Delete">
                    <Trash2 className="w-4 h-4" />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Plans ───────────────────────────────────────────────────────────────────

export const adminPlansRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/plans",
  component: AdminPlans,
});

function AdminPlans() {
  const queryClient = useQueryClient();
  const [editId, setEditId] = useState<number | null>(null);
  const [editData, setEditData] = useState<any>({});

  const { data: plans = [] } = useQuery<any[]>({
    queryKey: ["admin", "plans"],
    queryFn: () => api.get("/admin/plans"),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) => api.put(`/admin/plans/${id}`, data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ["admin", "plans"] }); setEditId(null); },
  });

  function startEdit(plan: any) {
    setEditId(plan.id);
    setEditData({ name: plan.name, price_cents: plan.price_cents, interval_days: plan.interval_days, max_images: plan.max_images, is_featured: plan.is_featured });
  }

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Listing Plans</h1>
      <div className="grid sm:grid-cols-3 gap-4">
        {plans.map((plan: any) => (
          <div key={plan.id} className="bg-white rounded-xl border border-border p-5">
            {editId === plan.id ? (
              <div className="space-y-3">
                <input type="text" value={editData.name} onChange={(e) => setEditData((p: any) => ({ ...p, name: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm font-semibold" />
                <div>
                  <label className="text-xs text-muted-foreground">Price (cents)</label>
                  <input type="number" value={editData.price_cents} onChange={(e) => setEditData((p: any) => ({ ...p, price_cents: parseInt(e.target.value) || 0 }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground">Duration (days)</label>
                  <input type="number" value={editData.interval_days} onChange={(e) => setEditData((p: any) => ({ ...p, interval_days: parseInt(e.target.value) || 90 }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground">Max Images</label>
                  <input type="number" value={editData.max_images} onChange={(e) => setEditData((p: any) => ({ ...p, max_images: parseInt(e.target.value) || 3 }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" />
                </div>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" checked={editData.is_featured} onChange={(e) => setEditData((p: any) => ({ ...p, is_featured: e.target.checked }))} />
                  Featured
                </label>
                <div className="flex gap-2">
                  <button onClick={() => updateMutation.mutate({ id: plan.id, data: editData })} className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Save</button>
                  <button onClick={() => setEditId(null)} className="text-sm text-muted-foreground hover:text-foreground px-3 py-2">Cancel</button>
                </div>
              </div>
            ) : (
              <>
                <div className="flex items-center justify-between mb-3">
                  <h3 className="font-heading font-semibold text-lg">{plan.name}</h3>
                  {plan.is_featured && <span className="px-2 py-0.5 rounded-full bg-accent/10 text-accent text-xs font-medium">Featured</span>}
                </div>
                <div className="text-3xl font-bold font-heading mb-1">${(plan.price_cents / 100).toFixed(2)}</div>
                <div className="text-sm text-muted-foreground mb-4">{plan.interval_days} days</div>
                <ul className="space-y-1.5 mb-4">
                  {(plan.features || []).map((f: string, i: number) => (
                    <li key={i} className="text-sm text-muted-foreground flex items-center gap-2">
                      <span className="w-1.5 h-1.5 rounded-full bg-accent" /> {f}
                    </li>
                  ))}
                </ul>
                <div className="text-xs text-muted-foreground mb-3">Max {plan.max_images} images</div>
                <button onClick={() => startEdit(plan)} className="w-full text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg px-4 py-2 transition-colors">Edit Plan</button>
              </>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

// ─── API Keys ────────────────────────────────────────────────────────────────

export const adminApiKeysRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/api-keys",
  component: AdminApiKeys,
});

function AdminApiKeys() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: "", service: "outscraper", key: "" });

  const { data: keys = [], error: keysError, isError: isKeysError } = useQuery<any[]>({
    queryKey: ["admin", "api-keys"],
    queryFn: () => api.get("/admin/api-keys"),
  });

  const createMutation = useMutation({
    mutationFn: (data: typeof form) => api.post("/admin/api-keys", data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ["admin", "api-keys"] }); setShowForm(false); setForm({ name: "", service: "outscraper", key: "" }); },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/api-keys/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "api-keys"] }),
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">API Keys (BYOK)</h1>
        <button onClick={() => setShowForm(!showForm)} className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <Plus className="w-4 h-4" /> Add Key
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl border border-border p-6 mb-6">
          <h3 className="font-semibold mb-4">Add New API Key</h3>
          <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Name</label>
              <input type="text" value={form.name} onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))} placeholder="e.g., Outscraper Production" className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm" required />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Service</label>
              <select value={form.service} onChange={(e) => setForm((p) => ({ ...p, service: e.target.value }))} className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm">
                <option value="outscraper">Outscraper</option>
                <option value="google_maps">Google Maps</option>
                <option value="crawl4ai">Crawl4AI</option>
                <option value="openai">OpenAI</option>
                <option value="anthropic">Anthropic</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium mb-1">API Key</label>
              <input type="password" value={form.key} onChange={(e) => setForm((p) => ({ ...p, key: e.target.value }))} placeholder="Paste your API key..." className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm font-mono" required />
              <p className="text-xs text-muted-foreground mt-1">Keys are encrypted with Fernet before storage.</p>
            </div>
            <div className="sm:col-span-2 flex gap-3">
              <button type="submit" className="bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2 rounded-lg transition-colors">Save Key</button>
              <button type="button" onClick={() => setShowForm(false)} className="text-sm text-muted-foreground hover:text-foreground px-4 py-2">Cancel</button>
            </div>
            {createMutation.isError && (
              <p className="sm:col-span-2 text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg">
                {(createMutation.error as Error)?.message || "Failed to save API key."}
              </p>
            )}
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl border border-border overflow-hidden">
        {isKeysError && (
          <p className="m-4 text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg">
            {(keysError as Error)?.message || "Failed to load API keys."}
          </p>
        )}
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">Name</th>
              <th className="text-left px-4 py-3 font-medium">Service</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-left px-4 py-3 font-medium">Last Used</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {keys.length === 0 ? (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">No API keys configured.</td></tr>
            ) : (
              keys.map((key: any) => (
                <tr key={key.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">{key.name}</td>
                  <td className="px-4 py-3"><span className="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs">{key.service}</span></td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${key.is_active ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"}`}>
                      {key.is_active ? "Active" : "Inactive"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : "Never"}</td>
                  <td className="px-4 py-3 text-right">
                    <button onClick={() => deleteMutation.mutate(key.id)} className="text-destructive hover:text-destructive/80 p-1" title="Delete"><Trash2 className="w-4 h-4" /></button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
        {deleteMutation.isError && (
          <p className="m-4 mt-0 text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg">
            {(deleteMutation.error as Error)?.message || "Failed to delete API key."}
          </p>
        )}
      </div>
    </div>
  );
}

// ─── Pipeline ────────────────────────────────────────────────────────────────

export const adminPipelineRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/pipeline",
  component: AdminPipeline,
});

function AdminPipeline() {
  const queryClient = useQueryClient();
  const [runMode, setRunMode] = useState("weekly");
  const [selectedRegions, setSelectedRegions] = useState<string[]>([]);

  const hasRunning = (runs: any[]) => runs.some((r: any) => r.status === "running" || r.status === "queued");

  const { data: runs = [] } = useQuery<any[]>({
    queryKey: ["admin", "pipeline", "runs"],
    queryFn: () => api.get("/admin/pipeline/runs"),
    refetchInterval: (query) => hasRunning(query.state.data ?? []) ? 3000 : false,
  });

  const { data: regions = [] } = useQuery<any[]>({
    queryKey: ["admin", "pipeline", "regions"],
    queryFn: () => api.get("/admin/pipeline/regions"),
    refetchInterval: (query) => hasRunning(runs) ? 10000 : false,
  });

  const { data: workerStatus } = useQuery<{ alive: boolean; workers?: string[]; active_tasks?: number; error?: string }>({
    queryKey: ["admin", "pipeline", "worker-status"],
    queryFn: () => api.get("/admin/pipeline/worker-status"),
    refetchInterval: 30000,
  });

  const triggerMutation = useMutation({
    mutationFn: (data: { mode: string; regions?: string[] }) => api.post("/admin/pipeline/run-inline", data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "pipeline"] }),
  });

  const cancelMutation = useMutation({
    mutationFn: (runId: number) => api.post(`/admin/pipeline/runs/${runId}/cancel`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "pipeline"] }),
  });

  const handleTrigger = () => {
    const payload: { mode: string; regions?: string[] } = { mode: runMode };
    if (selectedRegions.length > 0) payload.regions = selectedRegions;
    triggerMutation.mutate(payload);
  };

  const statusColor = (status: string) => {
    if (status === "completed") return "bg-green-50 text-green-700";
    if (status === "completed_with_errors") return "bg-yellow-50 text-yellow-700";
    if (status === "paused_no_credits") return "bg-amber-50 text-amber-700";
    if (status === "running") return "bg-blue-50 text-blue-700";
    if (status === "queued") return "bg-purple-50 text-purple-700";
    if (status === "failed") return "bg-red-50 text-red-700";
    return "bg-gray-50 text-gray-700";
  };

  // Find the active running run for the progress card
  const activeRun = runs.find((r: any) => r.status === "running" && r.stats?.progress);

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Pipeline Management</h1>

      {/* Worker status banner */}
      <div className={`flex items-center gap-2 px-4 py-2 rounded-lg mb-4 text-sm ${workerStatus?.alive ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"}`}>
        <Activity className="w-4 h-4" />
        {workerStatus?.alive
          ? `Worker online (${workerStatus.active_tasks ?? 0} active task${workerStatus.active_tasks !== 1 ? "s" : ""})`
          : "Worker offline — pipeline tasks will queue but not execute"
        }
      </div>

      {/* Live progress card for active run */}
      {activeRun && (
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-blue-500 animate-pulse" />
              <span className="font-semibold text-blue-900">Run #{activeRun.id} in progress</span>
            </div>
            <span className="text-sm text-blue-700">{activeRun.stats.progress}</span>
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
            <div className="bg-white/60 rounded-lg px-3 py-2">
              <div className="text-blue-600 text-xs">Current Region</div>
              <div className="font-semibold text-blue-900">{activeRun.stats.current_region || "Starting..."}</div>
            </div>
            <div className="bg-white/60 rounded-lg px-3 py-2">
              <div className="text-blue-600 text-xs">New Listings</div>
              <div className="font-semibold text-blue-900">{activeRun.stats.total_new ?? 0}</div>
            </div>
            <div className="bg-white/60 rounded-lg px-3 py-2">
              <div className="text-blue-600 text-xs">Regions Done</div>
              <div className="font-semibold text-blue-900">{activeRun.stats.regions_processed ?? 0}</div>
            </div>
            <div className="bg-white/60 rounded-lg px-3 py-2">
              <div className="text-blue-600 text-xs">Errors</div>
              <div className="font-semibold text-blue-900">{activeRun.stats.errors?.length ?? 0}</div>
            </div>
          </div>
        </div>
      )}

      <div className="bg-white rounded-xl border border-border p-6 mb-6">
        <h3 className="font-semibold mb-4">Run Pipeline</h3>
        <div className="flex flex-wrap items-end gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">Mode</label>
            <select value={runMode} onChange={(e) => setRunMode(e.target.value)} className="px-3 py-2 rounded-lg border border-border bg-background text-sm">
              <option value="backfill">Backfill (Resume-safe: never-scraped first)</option>
              <option value="weekly">Weekly (Top 5 Rotation)</option>
              <option value="monthly">Monthly (Re-verify All)</option>
            </select>
          </div>
          <div className="flex-1 min-w-[200px]">
            <label className="block text-sm font-medium mb-1">Specific Regions (optional)</label>
            <input
              type="text"
              value={selectedRegions.join(",")}
              onChange={(e) => setSelectedRegions(e.target.value ? e.target.value.split(",").map((s) => s.trim().toUpperCase()).filter(Boolean) : [])}
              placeholder="e.g., CA,TX,FL (leave empty for auto)"
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
            />
          </div>
          <button
            onClick={handleTrigger}
            disabled={triggerMutation.isPending || hasRunning(runs)}
            className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors disabled:opacity-50"
          >
            <Play className="w-4 h-4" /> {triggerMutation.isPending ? "Starting..." : hasRunning(runs) ? "Running..." : "Start Run"}
          </button>
        </div>
        {triggerMutation.isSuccess && (
          <p className="mt-3 text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg">Pipeline run queued. Monitoring below...</p>
        )}
        {triggerMutation.isError && (
          <p className="mt-3 text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg">Failed to start run. Check API keys are configured.</p>
        )}
      </div>

      <div className="bg-white rounded-xl border border-border overflow-hidden mb-6">
        <div className="px-4 py-3 border-b border-border flex items-center justify-between">
          <h3 className="font-semibold">Recent Runs</h3>
          {hasRunning(runs) && <span className="text-xs text-blue-600 animate-pulse">Auto-refreshing...</span>}
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">ID</th>
              <th className="text-left px-4 py-3 font-medium">Mode</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-left px-4 py-3 font-medium">Stats</th>
              <th className="text-left px-4 py-3 font-medium">Started</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {runs.length === 0 ? (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">No pipeline runs yet. Add an Outscraper API key and start a run.</td></tr>
            ) : (
              runs.slice(0, 15).map((run: any) => (
                <tr key={run.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">#{run.id}</td>
                  <td className="px-4 py-3 capitalize">{run.mode}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${statusColor(run.status)}`}>{run.status}</span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground text-xs">
                    {run.stats?.progress ? (
                      <span>{run.stats.progress} - +{run.stats.total_new} new, {run.stats.total_updated} updated{run.status === "paused_no_credits" ? " - credits exhausted (resume with Backfill)" : ""}</span>
                    ) : run.stats?.total_new != null ? (
                      <span>+{run.stats.total_new} new, {run.stats.total_updated} updated, {run.stats.regions_processed} regions</span>
                    ) : run.stats?.message ? (
                      <span>{run.stats.message}</span>
                    ) : run.error_message ? (
                      <span className="text-red-600">{run.error_message.slice(0, 80)}</span>
                    ) : (
                      <span>{run.regions?.join(", ") ?? "Auto"}</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{new Date(run.started_at).toLocaleString()}</td>
                  <td className="px-4 py-3 text-right">
                    {(run.status === "running" || run.status === "queued") && (
                      <button
                        onClick={() => { if (confirm("Cancel this pipeline run?")) cancelMutation.mutate(run.id); }}
                        className="text-red-600 hover:text-red-800 p-1"
                        title="Cancel run"
                      >
                        <XCircle className="w-4 h-4" />
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <div className="px-4 py-3 border-b border-border"><h3 className="font-semibold">Region Schedule</h3></div>
        <div className="max-h-96 overflow-y-auto">
          <table className="w-full text-sm">
            <thead className="sticky top-0 bg-white">
              <tr className="border-b border-border bg-muted/50">
                <th className="text-left px-4 py-3 font-medium">State</th>
                <th className="text-left px-4 py-3 font-medium">Priority</th>
                <th className="text-left px-4 py-3 font-medium">Listings</th>
                <th className="text-left px-4 py-3 font-medium">Last Scraped</th>
                <th className="text-left px-4 py-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              {regions.map((region: any) => (
                <tr key={region.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">
                    <div className="flex items-center gap-2">
                      <MapPin className="w-3.5 h-3.5 text-muted-foreground" />
                      {region.state_name} ({region.state_code})
                    </div>
                  </td>
                  <td className="px-4 py-3">{region.priority}/10</td>
                  <td className="px-4 py-3">{region.listing_count}</td>
                  <td className="px-4 py-3 text-muted-foreground">{region.last_scraped_at ? new Date(region.last_scraped_at).toLocaleDateString() : "Never"}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${region.enabled ? "bg-green-50 text-green-700" : "bg-gray-50 text-gray-700"}`}>
                      {region.enabled ? "Enabled" : "Disabled"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

// ─── Claims ─────────────────────────────────────────────────────────────────

export const adminClaimsRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/claims",
  component: AdminClaims,
});

function AdminClaims() {
  const queryClient = useQueryClient();
  const [statusFilter, setStatusFilter] = useState("pending");

  const { data: claims = [] } = useQuery<any[]>({
    queryKey: ["admin", "claims", statusFilter],
    queryFn: () => {
      const params = new URLSearchParams();
      if (statusFilter) params.set("status", statusFilter);
      return api.get(`/admin/listings/claims?${params}`);
    },
  });

  const resolveMutation = useMutation({
    mutationFn: ({ id, action, admin_note }: { id: number; action: string; admin_note?: string }) =>
      api.put(`/admin/listings/claims/${id}`, { action, admin_note }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "claims"] }),
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Listing Claims</h1>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
        >
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="">All</option>
        </select>
      </div>

      {claims.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          {statusFilter === "pending" ? "No pending claims." : "No claims found."}
        </div>
      ) : (
        <div className="space-y-3">
          {claims.map((claim: any) => (
            <div key={claim.id} className="bg-white rounded-xl border border-border p-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <span className="font-medium">{claim.listing_name}</span>
                    <span className={`px-2 py-0.5 rounded-full text-xs ${
                      claim.status === "pending" ? "bg-yellow-50 text-yellow-700" :
                      claim.status === "approved" ? "bg-green-50 text-green-700" :
                      "bg-red-50 text-red-700"
                    }`}>
                      {claim.status}
                    </span>
                  </div>
                  <div className="text-sm text-muted-foreground space-y-0.5">
                    <div>
                      {[claim.listing_city, claim.listing_state].filter(Boolean).join(", ")}
                      {claim.listing_slug && (
                        <> &middot; <a href={`/listing/${claim.listing_slug}`} className="text-accent hover:underline">View listing</a></>
                      )}
                    </div>
                    <div>Claimed by: <span className="font-medium text-foreground">{claim.user_email}</span></div>
                    {claim.business_name && <div>Business: {claim.business_name}</div>}
                    {claim.verification_note && <div>Note: "{claim.verification_note}"</div>}
                    <div className="text-xs">{new Date(claim.created_at).toLocaleString()}</div>
                  </div>
                </div>
                {claim.status === "pending" && (
                  <div className="flex items-center gap-2 ml-4">
                    <button
                      onClick={() => resolveMutation.mutate({ id: claim.id, action: "approve" })}
                      disabled={resolveMutation.isPending}
                      className="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                    >
                      <Check className="w-3.5 h-3.5" /> Approve
                    </button>
                    <button
                      onClick={() => resolveMutation.mutate({ id: claim.id, action: "reject" })}
                      disabled={resolveMutation.isPending}
                      className="flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                    >
                      <X className="w-3.5 h-3.5" /> Reject
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ─── Settings ────────────────────────────────────────────────────────────────

export const adminSettingsRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/settings",
  component: AdminSettings,
});

function AdminSettings() {
  const queryClient = useQueryClient();
  const [edits, setEdits] = useState<Record<string, string>>({});

  const { data: settings = [] } = useQuery<{ key: string; value: string; type: string }[]>({
    queryKey: ["admin", "settings"],
    queryFn: () => api.get("/admin/settings"),
  });

  const saveMutation = useMutation({
    mutationFn: (updates: { key: string; value: string }[]) => api.put("/admin/settings", updates),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ["admin", "settings"] }); setEdits({}); },
  });

  const labels: Record<string, string> = { site_name: "Site Name", site_tagline: "Tagline", contact_email: "Contact Email" };

  // ── Stripe Settings ──
  const [stripeForm, setStripeForm] = useState({ stripe_secret_key: "", stripe_webhook_secret: "", lead_price_cents: 1999 });
  const [stripeLoaded, setStripeLoaded] = useState(false);
  const [stripeSaved, setStripeSaved] = useState(false);

  const { data: stripeSettings } = useQuery<{
    stripe_secret_key: string; stripe_webhook_secret: string; lead_price_cents: number;
    has_stripe_key: boolean; has_webhook_secret: boolean;
  }>({
    queryKey: ["admin", "settings", "stripe"],
    queryFn: () => api.get("/admin/settings/stripe"),
  });

  // Populate form when data loads
  if (stripeSettings && !stripeLoaded) {
    setStripeForm({
      stripe_secret_key: stripeSettings.stripe_secret_key || "",
      stripe_webhook_secret: stripeSettings.stripe_webhook_secret || "",
      lead_price_cents: stripeSettings.lead_price_cents,
    });
    setStripeLoaded(true);
  }

  const [stripeError, setStripeError] = useState("");
  const stripeMutation = useMutation({
    mutationFn: (data: typeof stripeForm) => api.put("/admin/settings/stripe", data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin", "settings", "stripe"] });
      setStripeLoaded(false);
      setStripeSaved(true);
      setStripeError("");
      setTimeout(() => setStripeSaved(false), 3000);
    },
    onError: (err: Error) => {
      setStripeError(err.message || "Failed to save Stripe settings");
    },
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Settings</h1>

      {/* Site Settings */}
      <div className="mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-heading text-lg font-semibold">Site Settings</h2>
          {Object.keys(edits).length > 0 && (
            <button onClick={() => saveMutation.mutate(Object.entries(edits).map(([key, value]) => ({ key, value })))} className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
              <Save className="w-4 h-4" /> Save
            </button>
          )}
        </div>
        <div className="bg-white rounded-xl border border-border p-6 space-y-4">
          {settings.filter((s) => !s.key.startsWith("stripe_") && s.key !== "lead_price_cents").map((s) => (
            <div key={s.key}>
              <label className="block text-sm font-medium mb-1">{labels[s.key] || s.key}</label>
              <input
                type="text"
                value={edits[s.key] ?? s.value}
                onChange={(e) => setEdits((p) => ({ ...p, [s.key]: e.target.value }))}
                className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
              />
            </div>
          ))}
          {settings.length === 0 && <p className="text-muted-foreground text-sm">No settings found.</p>}
        </div>
      </div>

      {/* Stripe / Payment Settings */}
      <div>
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-heading text-lg font-semibold flex items-center gap-2">
            <CreditCard className="w-5 h-5" /> Stripe &amp; Payments
          </h2>
          {stripeSettings?.has_stripe_key ? (
            <span className="px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-xs font-medium">Connected</span>
          ) : (
            <span className="px-2.5 py-1 rounded-full bg-orange-50 text-orange-700 text-xs font-medium">Not Configured</span>
          )}
        </div>

        {stripeSaved && (
          <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg mb-4 text-sm">
            Stripe settings saved successfully.
          </div>
        )}
        {stripeError && (
          <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg mb-4 text-sm">
            Error: {stripeError}
          </div>
        )}

        <div className="bg-white rounded-xl border border-border p-6 space-y-4">
          <p className="text-sm text-muted-foreground">
            Configure Stripe only if you still want the legacy lead-unlock flow available. Get your keys from{" "}
            <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener noreferrer" className="text-accent hover:underline">
              Stripe Dashboard &rarr; API Keys
            </a>.
          </p>

          <div>
            <label className="block text-sm font-medium mb-1">Secret Key</label>
            <input
              type="password"
              value={stripeForm.stripe_secret_key}
              onChange={(e) => setStripeForm((p) => ({ ...p, stripe_secret_key: e.target.value }))}
              placeholder="sk_test_... or sk_live_..."
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm font-mono"
            />
            <p className="text-xs text-muted-foreground mt-1">Starts with sk_test_ (test mode) or sk_live_ (production).</p>
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Webhook Secret</label>
            <input
              type="password"
              value={stripeForm.stripe_webhook_secret}
              onChange={(e) => setStripeForm((p) => ({ ...p, stripe_webhook_secret: e.target.value }))}
              placeholder="whsec_..."
              className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm font-mono"
            />
            <p className="text-xs text-muted-foreground mt-1">
              Create a webhook at Stripe Dashboard &rarr; Webhooks pointing to <code className="bg-muted px-1 rounded">https://findsolarinstallers.xyz/api/stripe/webhook</code>
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Lead Price ($)</label>
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium">$</span>
              <input
                type="number"
                step="0.01"
                min="0"
                value={(stripeForm.lead_price_cents / 100).toFixed(2)}
                onChange={(e) => setStripeForm((p) => ({ ...p, lead_price_cents: Math.round(parseFloat(e.target.value || "0") * 100) }))}
                className="w-32 px-3 py-2 rounded-lg border border-border bg-background text-sm"
              />
              <span className="text-sm text-muted-foreground">legacy lead unlock</span>
            </div>
          </div>

          <button
            onClick={() => stripeMutation.mutate(stripeForm)}
            disabled={stripeMutation.isPending}
            className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors disabled:opacity-50"
          >
            <Save className="w-4 h-4" /> {stripeMutation.isPending ? "Saving..." : "Save Stripe Settings"}
          </button>
        </div>
      </div>
    </div>
  );
}
