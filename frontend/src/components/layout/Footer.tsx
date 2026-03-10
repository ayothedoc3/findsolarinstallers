import { Link } from "@tanstack/react-router";
import { Sun } from "lucide-react";

import { useMarketplaceData } from "@/lib/marketplace";

export function Footer() {
  const { data } = useMarketplaceData();
  const launchState = data?.launch_state || "our launch market";

  return (
    <footer className="bg-primary text-primary-foreground py-12 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="grid md:grid-cols-4 gap-8 mb-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Sun className="w-6 h-6 text-accent" />
              <span className="font-heading text-lg font-bold">Find Solar Installers</span>
            </div>
            <p className="text-sm text-slate-400">
              Verified featured solar installers, starting in {launchState}.
            </p>
          </div>
          <div>
            <h4 className="font-semibold mb-3">For Homeowners</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><Link to="/search" search={{ q: "", state: "", services: "", min_rating: undefined, financing: undefined, sort: "rating", page: 1 }} className="hover:text-white transition-colors">Find Installers</Link></li>
              <li><Link to="/categories" className="hover:text-white transition-colors">Browse Services</Link></li>
              <li><Link to="/contact" className="hover:text-white transition-colors">Contact</Link></li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold mb-3">For Installers</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><Link to="/for-installers" search={{ listing: "", state: "" }} className="hover:text-white transition-colors">Get Featured</Link></li>
              <li><Link to="/for-installers" search={{ listing: "", state: "" }} className="hover:text-white transition-colors">Pricing</Link></li>
              <li><a href="/dashboard" className="hover:text-white transition-colors">Dashboard</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold mb-3">Company</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><Link to="/about" className="hover:text-white transition-colors">About</Link></li>
              <li><Link to="/contact" className="hover:text-white transition-colors">Contact</Link></li>
              <li><Link to="/privacy" className="hover:text-white transition-colors">Privacy Policy</Link></li>
            </ul>
          </div>
        </div>
        <div className="border-t border-slate-700 pt-8 text-center text-sm text-slate-500">
          &copy; {new Date().getFullYear()} Find Solar Installers
        </div>
      </div>
    </footer>
  );
}
