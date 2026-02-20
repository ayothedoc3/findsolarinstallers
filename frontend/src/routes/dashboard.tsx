import { createRoute, Outlet, Link } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { LayoutDashboard, List, Inbox, User } from "lucide-react";

// Dashboard layout
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

// Dashboard overview
export const dashboardIndexRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/",
  component: DashboardOverview,
});

function DashboardOverview() {
  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Dashboard</h1>
      <div className="grid sm:grid-cols-3 gap-4 mb-8">
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Active Listings</div>
          <div className="text-2xl font-bold font-heading">0</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Total Leads</div>
          <div className="text-2xl font-bold font-heading">0</div>
        </div>
        <div className="bg-white rounded-xl border border-border p-5">
          <div className="text-sm text-muted-foreground mb-1">Profile Views</div>
          <div className="text-2xl font-bold font-heading">0</div>
        </div>
      </div>
      <div className="bg-white rounded-xl border border-border p-8 text-center">
        <h2 className="font-heading text-xl font-semibold mb-2">Get Started</h2>
        <p className="text-muted-foreground mb-4">Create your first listing to start receiving leads.</p>
        <Link
          to="/dashboard/listings"
          className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground font-semibold px-6 py-3 rounded-lg transition-colors"
        >
          <List className="w-4 h-4" /> Create Listing
        </Link>
      </div>
    </div>
  );
}

// My Listings
export const dashboardListingsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/listings",
  component: DashboardListings,
});

function DashboardListings() {
  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">My Listings</h1>
        <button className="flex items-center gap-2 bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <List className="w-4 h-4" /> New Listing
        </button>
      </div>
      <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
        You don't have any listings yet. Create one to get started.
      </div>
    </div>
  );
}

// Leads
export const dashboardLeadsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/leads",
  component: DashboardLeads,
});

function DashboardLeads() {
  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Leads</h1>
      <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
        No leads yet. Leads will appear here when customers contact you through your listings.
      </div>
    </div>
  );
}

// Profile
export const dashboardProfileRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/profile",
  component: DashboardProfile,
});

function DashboardProfile() {
  return (
    <div>
      <h1 className="font-heading text-2xl font-bold mb-6">Profile Settings</h1>
      <div className="bg-white rounded-xl border border-border p-6">
        <p className="text-muted-foreground">Profile editing coming in Phase 5.</p>
      </div>
    </div>
  );
}
