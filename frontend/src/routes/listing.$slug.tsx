import { createRoute } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import {
  Star,
  MapPin,
  Phone,
  Mail,
  Globe,
  Shield,
  Zap,
  Sun,
  ChevronRight,
  Clock,
  Award,
  DollarSign,
  Wrench,
} from "lucide-react";
import { useState } from "react";

export const listingDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/listing/$slug",
  component: ListingDetailPage,
});

interface Listing {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  zip_code: string | null;
  google_rating: number | null;
  total_reviews: number;
  services_offered: string[];
  panel_brands: string[];
  certifications: string[];
  financing_available: boolean;
  free_consultation: boolean;
  years_in_business: number | null;
  installations_completed: number | null;
  warranty_years: number | null;
  system_size_range: string | null;
  service_area_radius: string | null;
  images: { id: number; url: string; is_primary: boolean }[];
  categories: { id: number; name: string; slug: string }[];
  status: string;
  created_at: string;
}

function ListingDetailPage() {
  const { slug } = listingDetailRoute.useParams();
  const [showContactForm, setShowContactForm] = useState(false);
  const [contactForm, setContactForm] = useState({
    name: "",
    email: "",
    phone: "",
    message: "",
    project_type: "",
    zip_code: "",
  });
  const [contactSubmitted, setContactSubmitted] = useState(false);

  const { data: listing, isLoading, error } = useQuery<Listing>({
    queryKey: ["listing", slug],
    queryFn: () => api.get(`/listings/${slug}`),
  });

  const handleContactSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!listing) return;
    try {
      await api.post("/contact", { listing_id: listing.id, ...contactForm });
      setContactSubmitted(true);
      setShowContactForm(false);
    } catch {
      // Handle error
    }
  };

  if (isLoading) {
    return (
      <div className="max-w-5xl mx-auto px-4 py-16 text-center">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-muted rounded w-1/3 mx-auto" />
          <div className="h-4 bg-muted rounded w-1/2 mx-auto" />
          <div className="h-64 bg-muted rounded" />
        </div>
      </div>
    );
  }

  if (error || !listing) {
    return (
      <div className="max-w-5xl mx-auto px-4 py-16 text-center">
        <h1 className="font-heading text-2xl font-bold mb-4">Listing Not Found</h1>
        <p className="text-muted-foreground">The listing you're looking for doesn't exist or has been removed.</p>
        <a href="/search" className="inline-block mt-6 bg-accent text-accent-foreground px-6 py-3 rounded-lg font-semibold">
          Browse Installers
        </a>
      </div>
    );
  }

  const renderStars = (rating: number) => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      stars.push(
        <Star
          key={i}
          className={`w-5 h-5 ${i <= Math.round(rating) ? "fill-accent text-accent" : "text-muted"}`}
        />
      );
    }
    return stars;
  };

  return (
    <div className="max-w-5xl mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-sm text-muted-foreground mb-6">
        <a href="/" className="hover:text-foreground">Home</a>
        <ChevronRight className="w-4 h-4" />
        <a href="/search" className="hover:text-foreground">Solar Installers</a>
        <ChevronRight className="w-4 h-4" />
        <span className="text-foreground">{listing.name}</span>
      </nav>

      <div className="grid lg:grid-cols-3 gap-8">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Header Card */}
          <div className="bg-white rounded-xl border border-border p-6">
            <div className="flex items-start justify-between mb-4">
              <div>
                <h1 className="font-heading text-2xl md:text-3xl font-bold mb-2">
                  {listing.name}
                </h1>
                <div className="flex items-center gap-2 text-muted-foreground">
                  <MapPin className="w-4 h-4" />
                  <span>
                    {[listing.address, listing.city, listing.state, listing.zip_code]
                      .filter(Boolean)
                      .join(", ")}
                  </span>
                </div>
              </div>
            </div>

            {/* Rating */}
            {listing.google_rating && (
              <div className="flex items-center gap-3 mb-4 p-3 bg-accent/5 rounded-lg">
                <div className="text-3xl font-bold text-primary font-heading">
                  {listing.google_rating.toFixed(1)}
                </div>
                <div>
                  <div className="flex gap-0.5">{renderStars(listing.google_rating)}</div>
                  <div className="text-sm text-muted-foreground mt-0.5">
                    {listing.total_reviews} reviews
                  </div>
                </div>
              </div>
            )}

            {/* Certifications */}
            {listing.certifications.length > 0 && (
              <div className="flex flex-wrap gap-2 mb-4">
                {listing.certifications.map((cert) => (
                  <span
                    key={cert}
                    className="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded-full bg-green-50 text-green-700 border border-green-200"
                  >
                    <Shield className="w-3.5 h-3.5" />
                    {cert}
                  </span>
                ))}
              </div>
            )}

            {/* Contact Info */}
            <div className="grid sm:grid-cols-2 gap-3">
              {listing.phone && (
                <a href={`tel:${listing.phone}`} className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Phone className="w-4 h-4 text-accent" />
                  <span>{listing.phone}</span>
                </a>
              )}
              {listing.email && (
                <a href={`mailto:${listing.email}`} className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Mail className="w-4 h-4 text-accent" />
                  <span>{listing.email}</span>
                </a>
              )}
              {listing.website && (
                <a href={listing.website} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Globe className="w-4 h-4 text-accent" />
                  <span>Visit Website</span>
                </a>
              )}
            </div>
          </div>

          {/* Description */}
          {listing.description && (
            <div className="bg-white rounded-xl border border-border p-6">
              <h2 className="font-heading text-xl font-semibold mb-3">About</h2>
              <p className="text-muted-foreground leading-relaxed whitespace-pre-line">
                {listing.description}
              </p>
            </div>
          )}

          {/* Services */}
          {listing.services_offered.length > 0 && (
            <div className="bg-white rounded-xl border border-border p-6">
              <h2 className="font-heading text-xl font-semibold mb-4">Services</h2>
              <div className="grid sm:grid-cols-2 gap-3">
                {listing.services_offered.map((service) => (
                  <div key={service} className="flex items-center gap-2 text-sm">
                    <Zap className="w-4 h-4 text-accent shrink-0" />
                    <span>{service}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Panel Brands */}
          {listing.panel_brands.length > 0 && (
            <div className="bg-white rounded-xl border border-border p-6">
              <h2 className="font-heading text-xl font-semibold mb-4">Panel Brands</h2>
              <div className="flex flex-wrap gap-2">
                {listing.panel_brands.map((brand) => (
                  <span key={brand} className="text-sm px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                    {brand}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Company Details */}
          <div className="bg-white rounded-xl border border-border p-6">
            <h2 className="font-heading text-xl font-semibold mb-4">Company Details</h2>
            <div className="grid sm:grid-cols-2 gap-4">
              {listing.years_in_business && (
                <div className="flex items-center gap-3">
                  <Clock className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">Years in Business</div>
                    <div className="font-medium">{listing.years_in_business}</div>
                  </div>
                </div>
              )}
              {listing.installations_completed && (
                <div className="flex items-center gap-3">
                  <Wrench className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">Installations Completed</div>
                    <div className="font-medium">{listing.installations_completed.toLocaleString()}</div>
                  </div>
                </div>
              )}
              {listing.warranty_years && (
                <div className="flex items-center gap-3">
                  <Award className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">Warranty</div>
                    <div className="font-medium">{listing.warranty_years} years</div>
                  </div>
                </div>
              )}
              {listing.financing_available && (
                <div className="flex items-center gap-3">
                  <DollarSign className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">Financing</div>
                    <div className="font-medium">Available</div>
                  </div>
                </div>
              )}
              {listing.system_size_range && (
                <div className="flex items-center gap-3">
                  <Sun className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">System Size Range</div>
                    <div className="font-medium">{listing.system_size_range}</div>
                  </div>
                </div>
              )}
              {listing.service_area_radius && (
                <div className="flex items-center gap-3">
                  <MapPin className="w-5 h-5 text-muted-foreground" />
                  <div>
                    <div className="text-sm text-muted-foreground">Service Area</div>
                    <div className="font-medium">{listing.service_area_radius}</div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <aside className="lg:col-span-1">
          <div className="sticky top-20 space-y-4">
            {/* CTA Card */}
            <div className="bg-white rounded-xl border border-border p-6">
              <h3 className="font-heading text-lg font-semibold mb-2">
                Get a Free Quote
              </h3>
              <p className="text-sm text-muted-foreground mb-4">
                Contact {listing.name} for a free solar consultation.
              </p>

              {contactSubmitted ? (
                <div className="text-center py-4">
                  <div className="text-success text-lg font-semibold mb-1">Request Sent!</div>
                  <p className="text-sm text-muted-foreground">
                    {listing.name} will get back to you soon.
                  </p>
                </div>
              ) : showContactForm ? (
                <form onSubmit={handleContactSubmit} className="space-y-3">
                  <input
                    type="text"
                    placeholder="Your Name"
                    value={contactForm.name}
                    onChange={(e) => setContactForm((p) => ({ ...p, name: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                    required
                  />
                  <input
                    type="email"
                    placeholder="Email Address"
                    value={contactForm.email}
                    onChange={(e) => setContactForm((p) => ({ ...p, email: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                    required
                  />
                  <input
                    type="tel"
                    placeholder="Phone (optional)"
                    value={contactForm.phone}
                    onChange={(e) => setContactForm((p) => ({ ...p, phone: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                  />
                  <select
                    value={contactForm.project_type}
                    onChange={(e) => setContactForm((p) => ({ ...p, project_type: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                  >
                    <option value="">Project Type...</option>
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="battery">Battery Storage</option>
                    <option value="maintenance">Maintenance</option>
                  </select>
                  <input
                    type="text"
                    placeholder="ZIP Code"
                    value={contactForm.zip_code}
                    onChange={(e) => setContactForm((p) => ({ ...p, zip_code: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm"
                  />
                  <textarea
                    placeholder="Tell us about your project..."
                    value={contactForm.message}
                    onChange={(e) => setContactForm((p) => ({ ...p, message: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm min-h-[80px]"
                  />
                  <button
                    type="submit"
                    className="w-full bg-accent hover:bg-accent/90 text-accent-foreground font-semibold py-3 rounded-lg transition-colors"
                  >
                    Send Request
                  </button>
                </form>
              ) : (
                <button
                  onClick={() => setShowContactForm(true)}
                  className="w-full bg-accent hover:bg-accent/90 text-accent-foreground font-semibold py-3 rounded-lg transition-colors"
                >
                  Request Free Quote
                </button>
              )}

              {listing.free_consultation && (
                <p className="text-xs text-center text-muted-foreground mt-3">
                  Free consultation available
                </p>
              )}
            </div>

            {/* Quick Facts */}
            {listing.phone && (
              <a
                href={`tel:${listing.phone}`}
                className="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-3 rounded-xl transition-colors"
              >
                <Phone className="w-4 h-4" />
                Call Now
              </a>
            )}
          </div>
        </aside>
      </div>
    </div>
  );
}
