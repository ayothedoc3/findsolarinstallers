import { createRoute } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useQuery, useMutation } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { getUtmPayload, useMarketplaceData } from "@/lib/marketplace";
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
  Building2,
} from "lucide-react";
import { useState, useMemo } from "react";
import { usePageTitle, useJsonLd } from "@/lib/seo";

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
  plan_name?: string | null;
  current_plan?: string | null;
  is_featured?: boolean;
  show_direct_contact?: boolean;
  verification_label?: string | null;
  is_claimed?: boolean;
}

function ListingDetailPage() {
  const { slug } = listingDetailRoute.useParams();
  const { data: marketplace } = useMarketplaceData();
  const [showContactForm, setShowContactForm] = useState(false);
  const [contactForm, setContactForm] = useState({
    name: "",
    email: "",
    phone: "",
    message: "",
    project_type: "",
    zip_code: "",
    consent: false,
    hp: "",
  });
  const [contactSubmitted, setContactSubmitted] = useState(false);
  const [contactError, setContactError] = useState("");
  const [showClaimForm, setShowClaimForm] = useState(false);
  const [claimNote, setClaimNote] = useState("");
  const [claimSubmitted, setClaimSubmitted] = useState(false);

  const { data: listing, isLoading, error } = useQuery<Listing>({
    queryKey: ["listing", slug],
    queryFn: () => api.get(`/listings/${slug}`),
  });

  // SEO
  const location = listing ? [listing.city, listing.state].filter(Boolean).join(", ") : "";
  const launchState = marketplace?.launch_state || listing?.state || "our launch market";
  usePageTitle(listing ? `${listing.name} — Solar Installer in ${location}` : "");

  const jsonLd = useMemo(() => {
    if (!listing) return null;
    return {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      name: listing.name,
      description: listing.description || `${listing.name} is a solar installer in ${location}.`,
      address: {
        "@type": "PostalAddress",
        streetAddress: listing.address,
        addressLocality: listing.city,
        addressRegion: listing.state,
        postalCode: listing.zip_code,
        addressCountry: "US",
      },
      ...(listing.phone && { telephone: listing.phone }),
      ...(listing.website && { url: listing.website }),
      ...(listing.google_rating && {
        aggregateRating: {
          "@type": "AggregateRating",
          ratingValue: listing.google_rating,
          reviewCount: listing.total_reviews,
          bestRating: 5,
        },
      }),
    };
  }, [listing, location]);
  useJsonLd(jsonLd);

  const claimMutation = useMutation({
    mutationFn: () => api.post(`/listings/${slug}/claim`, { verification_note: claimNote }),
    onSuccess: () => {
      setClaimSubmitted(true);
      setShowClaimForm(false);
    },
  });

  const handleContactSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!listing) return;
    setContactError("");
    try {
      await api.post("/contact", {
        listing_id: listing.id,
        ...contactForm,
        page_path: window.location.pathname,
        ...getUtmPayload(window.location.search),
      });
      setContactSubmitted(true);
      setShowContactForm(false);
    } catch (err) {
      setContactError((err as Error)?.message || "Failed to submit your request.");
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
                <div className="flex flex-wrap items-center gap-2 mb-2">
                  <h1 className="font-heading text-2xl md:text-3xl font-bold">
                    {listing.name}
                  </h1>
                  <span className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold ${
                    listing.is_featured
                      ? "bg-accent/10 text-accent"
                      : "bg-muted text-muted-foreground"
                  }`}>
                    <Shield className="w-3.5 h-3.5" />
                    {listing.verification_label || "Basic Profile"}
                  </span>
                </div>
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
              {listing.show_direct_contact && listing.phone && (
                <a href={`tel:${listing.phone}`} className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Phone className="w-4 h-4 text-accent" />
                  <span>{listing.phone}</span>
                </a>
              )}
              {listing.show_direct_contact && listing.email && (
                <a href={`mailto:${listing.email}`} className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Mail className="w-4 h-4 text-accent" />
                  <span>{listing.email}</span>
                </a>
              )}
              {listing.show_direct_contact && listing.website && (
                <a href={listing.website} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 text-sm p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                  <Globe className="w-4 h-4 text-accent" />
                  <span>Visit Website</span>
                </a>
              )}
              {!listing.show_direct_contact && (
                <div className="sm:col-span-2 rounded-lg border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                  Direct phone, email, and website links are only shown on Verified Featured
                  profiles. Use the quote form so the request stays attributable.
                </div>
              )}
            </div>
          </div>

          {listing.is_featured && listing.images.length > 0 && (
            <div className="bg-white rounded-xl border border-border p-6">
              <h2 className="font-heading text-xl font-semibold mb-4">Profile Gallery</h2>
              <div className="grid sm:grid-cols-2 gap-4">
                {listing.images.slice(0, 4).map((image) => (
                  <div key={image.id} className="overflow-hidden rounded-xl border border-border bg-muted">
                    <img src={image.url} alt={listing.name} className="h-48 w-full object-cover" />
                  </div>
                ))}
              </div>
            </div>
          )}

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
            <div className={`rounded-xl border p-5 ${
              listing.is_featured
                ? "border-accent/30 bg-accent/5"
                : "border-border bg-white"
            }`}>
              <h3 className="font-heading text-lg font-semibold mb-2">
                {listing.verification_label || "Basic Profile"}
              </h3>
              <p className="text-sm text-muted-foreground">
                {listing.is_featured
                  ? `This installer is part of the paid featured launch in ${launchState}. Direct contact details are visible and dashboard proof is enabled.`
                  : "This is a basic directory profile. Direct contact details stay hidden until the installer upgrades to a featured placement."}
              </p>
            </div>

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
                  <input
                    type="text"
                    value={contactForm.hp}
                    onChange={(e) => setContactForm((p) => ({ ...p, hp: e.target.value }))}
                    className="hidden"
                    tabIndex={-1}
                    autoComplete="off"
                  />
                  <label className="flex items-start gap-2 text-xs text-muted-foreground">
                    <input
                      type="checkbox"
                      checked={contactForm.consent}
                      onChange={(e) => setContactForm((p) => ({ ...p, consent: e.target.checked }))}
                      className="mt-0.5"
                      required
                    />
                    <span>
                      I consent to sharing my information with this installer for follow-up
                      about my solar project.
                    </span>
                  </label>
                  {contactError && (
                    <p className="text-xs text-red-600">{contactError}</p>
                  )}
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
            {listing.show_direct_contact && listing.phone && (
              <a
                href={`tel:${listing.phone}`}
                className="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-3 rounded-xl transition-colors"
              >
                <Phone className="w-4 h-4" />
                Call Now
              </a>
            )}

            {/* Claim Listing */}
            {!listing.is_claimed && (
              <div className="bg-white rounded-xl border border-border p-4">
                <a
                  href={`/for-installers?listing=${listing.slug}&state=${encodeURIComponent(listing.state || launchState)}`}
                  className="flex items-center justify-center gap-2 w-full rounded-lg bg-accent px-4 py-3 text-sm font-semibold text-accent-foreground hover:bg-accent/90"
                >
                  <Building2 className="w-4 h-4" />
                  Claim This Profile
                </a>
                <p className="mt-3 text-xs text-muted-foreground">
                  We verify ownership and onboard featured placements manually.
                </p>

                {claimSubmitted ? (
                  <div className="text-center py-2 mt-3">
                    <div className="text-green-700 font-semibold text-sm mb-1">Claim Submitted</div>
                    <p className="text-xs text-muted-foreground">An admin will review your claim shortly.</p>
                  </div>
                ) : showClaimForm ? (
                  <div className="space-y-3 mt-4">
                    <h4 className="font-semibold text-sm">Claim this listing</h4>
                    <p className="text-xs text-muted-foreground">
                      Prove you own this business to manage leads and receive customer inquiries.
                    </p>
                    <textarea
                      placeholder="How can you verify ownership? (e.g., I'm the owner, my email is on the website, etc.)"
                      value={claimNote}
                      onChange={(e) => setClaimNote(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg border border-border bg-background text-sm min-h-[60px]"
                    />
                    <div className="flex gap-2">
                      <button
                        onClick={() => claimMutation.mutate()}
                        disabled={claimMutation.isPending}
                        className="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground text-xs font-semibold py-2 rounded-lg transition-colors disabled:opacity-50"
                      >
                        {claimMutation.isPending ? "Submitting..." : "Submit Claim"}
                      </button>
                      <button
                        onClick={() => setShowClaimForm(false)}
                        className="text-xs text-muted-foreground hover:text-foreground px-3"
                      >
                        Cancel
                      </button>
                    </div>
                    {claimMutation.isError && (
                      <p className="text-xs text-red-600">
                        {(claimMutation.error as Error)?.message || "Failed to submit claim. Please log in first."}
                      </p>
                    )}
                  </div>
                ) : (
                  <button
                    onClick={() => setShowClaimForm(true)}
                    className="mt-4 w-full text-sm text-muted-foreground hover:text-foreground py-2 transition-colors"
                  >
                    Already have an owner account? Submit an authenticated claim
                  </button>
                )}
              </div>
            )}
          </div>
        </aside>
      </div>
    </div>
  );
}
