import { Link } from "@tanstack/react-router";
import { Sun, Menu, X, User, Shield, LogOut } from "lucide-react";
import { useState } from "react";
import { isAuthenticated, getUserRole, logout } from "@/lib/auth";
import { useMarketplaceData } from "@/lib/marketplace";

export function Header() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const authed = isAuthenticated();
  const role = getUserRole();
  const isAdmin = role === "admin";
  const { data } = useMarketplaceData();
  const launchState = data?.launch_state || "our launch market";

  return (
    <header className="bg-white border-b border-border sticky top-0 z-50">
      <div className="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <Link to="/" className="flex items-center gap-2">
          <Sun className="w-8 h-8 text-accent" />
          <span className="font-heading text-xl font-bold text-primary">
            Find Solar Installers
          </span>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden md:flex items-center gap-6">
          <Link to="/search" search={{ q: "", state: "", services: "", min_rating: undefined, financing: undefined, sort: "rating", page: 1 }} className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
            Find Installers
          </Link>
          <Link to="/categories" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
            Categories
          </Link>
          <Link to="/for-installers" search={{ listing: "", state: "" }} className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
            For Installers
          </Link>
          {authed ? (
            <div className="flex items-center gap-3">
              {isAdmin && (
                <a href="/admin" className="flex items-center gap-1.5 text-sm font-medium text-accent hover:text-accent/80 transition-colors">
                  <Shield className="w-4 h-4" /> Admin
                </a>
              )}
              <a href="/dashboard" className="flex items-center gap-2 text-sm font-medium bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                <User className="w-4 h-4" /> Dashboard
              </a>
              <button
                onClick={logout}
                className="flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-destructive transition-colors"
              >
                <LogOut className="w-4 h-4" /> Sign Out
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-3">
              <Link to="/login" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                Sign In
              </Link>
              <Link to="/for-installers" search={{ listing: "", state: "" }} className="text-sm font-medium bg-accent text-accent-foreground px-4 py-2 rounded-lg hover:bg-accent/90 transition-colors">
                Get Featured in {launchState}
              </Link>
            </div>
          )}
        </nav>

        {/* Mobile Menu Toggle */}
        <button className="md:hidden p-2" onClick={() => setMobileOpen(!mobileOpen)}>
          {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Mobile Nav */}
      {mobileOpen && (
        <nav className="md:hidden border-t border-border bg-white p-4 space-y-3">
          <a href="/search" className="block text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
            Find Installers
          </a>
          <a href="/categories" className="block text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
            Categories
          </a>
          <a href="/for-installers" className="block text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
            For Installers
          </a>
          {authed ? (
            <>
              {isAdmin && (
                <a href="/admin" className="flex items-center gap-2 text-sm font-medium text-accent py-2" onClick={() => setMobileOpen(false)}>
                  <Shield className="w-4 h-4" /> Admin Panel
                </a>
              )}
              <a href="/dashboard" className="flex items-center gap-2 text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
                <User className="w-4 h-4" /> Dashboard
              </a>
              <button
                onClick={() => { setMobileOpen(false); logout(); }}
                className="flex items-center gap-2 text-sm font-medium text-destructive py-2 w-full text-left"
              >
                <LogOut className="w-4 h-4" /> Sign Out
              </button>
            </>
          ) : (
            <>
              <Link to="/login" className="block text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
                Sign In
              </Link>
              <Link to="/for-installers" search={{ listing: "", state: "" }} className="block text-sm font-medium bg-accent text-accent-foreground px-4 py-2 rounded-lg text-center" onClick={() => setMobileOpen(false)}>
                Get Featured
              </Link>
            </>
          )}
        </nav>
      )}
    </header>
  );
}
