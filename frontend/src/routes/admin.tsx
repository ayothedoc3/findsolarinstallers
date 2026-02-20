import { createRoute, Outlet, Link, useNavigate } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "@/lib/api";
import {
  LayoutDashboard, List, Users, Key, Workflow, Settings,
  Plus, Trash2, Star, Eye, Pencil, Play, MapPin,
} from "lucide-react";
import { useState } from "react";

// Admin layout route
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
    { to: "/admin/api-keys", icon: Key, label: "API Keys" },
    { to: "/admin/pipeline", icon: Workflow, label: "Pipeline" },
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

// Dashboard overview
export const adminIndexRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/",
  component: AdminDashboard,
});

function AdminDashboard() {
  const { data: stats } = useQuery({
    queryKey: ["admin", "stats"],
    queryFn: () => api.get<{ total_listings: number; total_states: number; total_reviews: number }>("/stats"),
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Admin Dashboard</h1>
      <div className="grid sm:grid-cols-3 gap-4 mb-8">
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Total Listings</div>
          <div className="text-2xl font-bold font-heading">{stats?.total_listings ?? "..."}</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">States Covered</div>
          <div className="text-2xl font-bold font-heading">{stats?.total_states ?? "..."}</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Total Reviews</div>
          <div className="text-2xl font-bold font-heading">{stats?.total_reviews?.toLocaleString() ?? "..."}</div>
        </div>
      </div>
    </div>
  );
}

// API Keys (BYOK)
export const adminApiKeysRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/api-keys",
  component: AdminApiKeys,
});

interface ApiKeyItem {
  id: number;
  name: string;
  service: string;
  is_active: boolean;
  last_used_at: string | null;
  created_at: string;
}

function AdminApiKeys() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: "", service: "outscraper", key: "" });

  const { data: keys = [] } = useQuery<ApiKeyItem[]>({
    queryKey: ["admin", "api-keys"],
    queryFn: () => api.get("/admin/api-keys"),
  });

  const createMutation = useMutation({
    mutationFn: (data: typeof form) => api.post("/admin/api-keys", data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin", "api-keys"] });
      setShowForm(false);
      setForm({ name: "", service: "outscraper", key: "" });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/api-keys/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "api-keys"] }),
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">API Keys (BYOK)</h1>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
        >
          <Plus className="w-4 h-4" /> Add Key
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl border border-border p-6 mb-6">
          <h3 className="font-semibold mb-4">Add New API Key</h3>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              createMutation.mutate(form);
            }}
            className="grid sm:grid-cols-2 gap-4"
          >
            <div>
              <label className="block text-sm font-medium mb-1">Name</label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
                placeholder="e.g., Outscraper Production"
                className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Service</label>
              <select
                value={form.service}
                onChange={(e) => setForm((p) => ({ ...p, service: e.target.value }))}
                className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
              >
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
              <input
                type="password"
                value={form.key}
                onChange={(e) => setForm((p) => ({ ...p, key: e.target.value }))}
                placeholder="Paste your API key..."
                className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm font-mono"
                required
              />
              <p className="text-xs text-muted-foreground mt-1">
                Keys are encrypted with Fernet before storage. They are never exposed via the API.
              </p>
            </div>
            <div className="sm:col-span-2 flex gap-3">
              <button type="submit" className="bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2 rounded-lg transition-colors">
                Save Key
              </button>
              <button type="button" onClick={() => setShowForm(false)} className="text-sm text-muted-foreground hover:text-foreground px-4 py-2">
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl border border-border overflow-hidden">
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
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                  No API keys configured. Add one to get started with pipeline automation.
                </td>
              </tr>
            ) : (
              keys.map((key) => (
                <tr key={key.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">{key.name}</td>
                  <td className="px-4 py-3">
                    <span className="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs">
                      {key.service}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${key.is_active ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"}`}>
                      {key.is_active ? "Active" : "Inactive"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : "Never"}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => deleteMutation.mutate(key.id)}
                      className="text-destructive hover:text-destructive/80 p-1"
                      title="Delete key"
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

// Pipeline Management
export const adminPipelineRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/pipeline",
  component: AdminPipeline,
});

interface PipelineRun {
  id: number;
  mode: string;
  status: string;
  regions: string[] | null;
  stats: Record<string, number> | null;
  error_message: string | null;
  started_at: string;
  completed_at: string | null;
}

interface Region {
  id: number;
  state_code: string;
  state_name: string;
  priority: number;
  enabled: boolean;
  last_scraped_at: string | null;
  listing_count: number;
}

function AdminPipeline() {
  const queryClient = useQueryClient();
  const [runMode, setRunMode] = useState("weekly");

  const { data: runs = [] } = useQuery<PipelineRun[]>({
    queryKey: ["admin", "pipeline", "runs"],
    queryFn: () => api.get("/admin/pipeline/runs"),
  });

  const { data: regions = [] } = useQuery<Region[]>({
    queryKey: ["admin", "pipeline", "regions"],
    queryFn: () => api.get("/admin/pipeline/regions"),
  });

  const triggerMutation = useMutation({
    mutationFn: (data: { mode: string }) => api.post("/admin/pipeline/run", data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "pipeline"] }),
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Pipeline Management</h1>

      {/* Trigger Panel */}
      <div className="bg-white rounded-xl border border-border p-6 mb-6">
        <h3 className="font-semibold mb-4">Run Pipeline</h3>
        <div className="flex items-end gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">Mode</label>
            <select
              value={runMode}
              onChange={(e) => setRunMode(e.target.value)}
              className="px-3 py-2 rounded-lg border border-border bg-background text-sm"
            >
              <option value="backfill">Backfill (All States)</option>
              <option value="weekly">Weekly (Rotation)</option>
              <option value="monthly">Monthly (Re-verify)</option>
            </select>
          </div>
          <button
            onClick={() => triggerMutation.mutate({ mode: runMode })}
            className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors"
          >
            <Play className="w-4 h-4" /> Start Run
          </button>
        </div>
      </div>

      {/* Recent Runs */}
      <div className="bg-white rounded-xl border border-border overflow-hidden mb-6">
        <div className="px-4 py-3 border-b border-border">
          <h3 className="font-semibold">Recent Runs</h3>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">ID</th>
              <th className="text-left px-4 py-3 font-medium">Mode</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-left px-4 py-3 font-medium">Regions</th>
              <th className="text-left px-4 py-3 font-medium">Started</th>
            </tr>
          </thead>
          <tbody>
            {runs.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                  No pipeline runs yet.
                </td>
              </tr>
            ) : (
              runs.slice(0, 10).map((run) => (
                <tr key={run.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">#{run.id}</td>
                  <td className="px-4 py-3 capitalize">{run.mode}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs ${
                      run.status === "completed" ? "bg-green-50 text-green-700" :
                      run.status === "running" ? "bg-blue-50 text-blue-700" :
                      run.status === "failed" ? "bg-red-50 text-red-700" :
                      "bg-gray-50 text-gray-700"
                    }`}>
                      {run.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {run.regions?.join(", ") ?? "All"}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {new Date(run.started_at).toLocaleString()}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Region Schedule */}
      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <div className="px-4 py-3 border-b border-border">
          <h3 className="font-semibold">Region Schedule</h3>
        </div>
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
              {regions.map((region) => (
                <tr key={region.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">
                    <div className="flex items-center gap-2">
                      <MapPin className="w-3.5 h-3.5 text-muted-foreground" />
                      {region.state_name} ({region.state_code})
                    </div>
                  </td>
                  <td className="px-4 py-3">{region.priority}/10</td>
                  <td className="px-4 py-3">{region.listing_count}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {region.last_scraped_at ? new Date(region.last_scraped_at).toLocaleDateString() : "Never"}
                  </td>
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

// Admin Listings
export const adminListingsRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/listings",
  component: AdminListings,
});

function AdminListings() {
  const { data } = useQuery({
    queryKey: ["admin", "listings"],
    queryFn: () => api.get<{ items: any[]; total: number }>("/listings?per_page=50"),
  });

  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Manage Listings</h1>
      <div className="bg-white rounded-xl border border-border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/50">
              <th className="text-left px-4 py-3 font-medium">Name</th>
              <th className="text-left px-4 py-3 font-medium">Location</th>
              <th className="text-left px-4 py-3 font-medium">Rating</th>
              <th className="text-left px-4 py-3 font-medium">Status</th>
              <th className="text-right px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {!data?.items?.length ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                  No listings yet. Run the pipeline to import data.
                </td>
              </tr>
            ) : (
              data.items.map((listing: any) => (
                <tr key={listing.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium">{listing.name}</td>
                  <td className="px-4 py-3 text-muted-foreground">{listing.city}, {listing.state}</td>
                  <td className="px-4 py-3">
                    {listing.google_rating && (
                      <span className="flex items-center gap-1">
                        <Star className="w-3.5 h-3.5 fill-accent text-accent" />
                        {listing.google_rating}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <span className="px-2 py-1 rounded-full text-xs bg-green-50 text-green-700">
                      active
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right flex justify-end gap-2">
                    <a href={`/listing/${listing.slug}`} className="text-muted-foreground hover:text-foreground p-1">
                      <Eye className="w-4 h-4" />
                    </a>
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

// Admin Users
export const adminUsersRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/users",
  component: AdminUsers,
});

function AdminUsers() {
  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Manage Users</h1>
      <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
        User management coming in Phase 3.
      </div>
    </div>
  );
}
