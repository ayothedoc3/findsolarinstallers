import { Sun } from "lucide-react";

export function Footer() {
  return (
    <footer className="bg-primary text-primary-foreground py-12 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="grid md:grid-cols-4 gap-8 mb-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Sun className="w-6 h-6 text-accent" />
              <span className="font-heading text-lg font-bold">SolarListings</span>
            </div>
            <p className="text-sm text-slate-400">
              Find trusted solar installers near you. Compare ratings, read
              reviews, and get free quotes.
            </p>
          </div>
          <div>
            <h4 className="font-semibold mb-3">For Homeowners</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><a href="/search" className="hover:text-white transition-colors">Find Installers</a></li>
              <li><a href="/categories" className="hover:text-white transition-colors">Browse Services</a></li>
              <li><a href="#" className="hover:text-white transition-colors">Solar Guide</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold mb-3">For Installers</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><a href="/register" className="hover:text-white transition-colors">List Your Company</a></li>
              <li><a href="#" className="hover:text-white transition-colors">Pricing</a></li>
              <li><a href="#" className="hover:text-white transition-colors">Dashboard</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold mb-3">Company</h4>
            <ul className="space-y-2 text-sm text-slate-400">
              <li><a href="#" className="hover:text-white transition-colors">About</a></li>
              <li><a href="#" className="hover:text-white transition-colors">Contact</a></li>
              <li><a href="#" className="hover:text-white transition-colors">Privacy Policy</a></li>
            </ul>
          </div>
        </div>
        <div className="border-t border-slate-700 pt-8 text-center text-sm text-slate-500">
          &copy; {new Date().getFullYear()} SolarListings &mdash; Find Solar Installers Near You
        </div>
      </div>
    </footer>
  );
}
