import { useQuery } from "@tanstack/react-query";

import { api } from "./api";

export interface PublicPlan {
  id: number;
  name: string;
  price_cents: number;
  interval_days: number;
  max_images: number;
  is_featured: boolean;
  features: string[];
}

export interface MarketplaceData {
  launch_state: string;
  launch_state_code: string | null;
  contact_email: string;
  plans: PublicPlan[];
}

export function useMarketplaceData() {
  return useQuery<MarketplaceData>({
    queryKey: ["public", "plans"],
    queryFn: () => api.get("/plans/public"),
  });
}

export function getUtmPayload(search: string) {
  const params = new URLSearchParams(search);
  return {
    utm_source: params.get("utm_source") || undefined,
    utm_medium: params.get("utm_medium") || undefined,
    utm_campaign: params.get("utm_campaign") || undefined,
  };
}
