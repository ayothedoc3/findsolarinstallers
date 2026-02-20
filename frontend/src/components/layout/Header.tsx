import { Link } from "@tanstack/react-router";
import { Sun, Menu, X, User } from "lucide-react";
import { useState } from "react";
import { isAuthenticated } from "@/lib/auth";

export function Header() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const authed = isAuthenticated();

  return (
    <header className="bg-white border-b border-border sticky top-0 z-50">
      <div className="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <Link to="/" className="flex items-center gap-2">
          <Sun className="w-8 h-8 text-accent" />
          <span className="font-heading text-xl font-bold text-primary">
            SolarListings
          </span>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden md:flex items-center gap-6">
          <a href="/search" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
            Find Installers
          </a>
          <a href="/categories" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
            Categories
          </a>
          {authed ? (
            <a href="/dashboard" className="flex items-center gap-2 text-sm font-medium bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
              <User className="w-4 h-4" /> Dashboard
            </a>
          ) : (
            <div className="flex items-center gap-3">
              <Link to="/login" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                Sign In
              </Link>
              <Link to="/register" className="text-sm font-medium bg-accent text-accent-foreground px-4 py-2 rounded-lg hover:bg-accent/90 transition-colors">
                List Your Company
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
          <Link to="/login" className="block text-sm font-medium py-2" onClick={() => setMobileOpen(false)}>
            Sign In
          </Link>
          <Link to="/register" className="block text-sm font-medium bg-accent text-accent-foreground px-4 py-2 rounded-lg text-center" onClick={() => setMobileOpen(false)}>
            List Your Company
          </Link>
        </nav>
      )}
    </header>
  );
}
