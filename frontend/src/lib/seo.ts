import { useEffect } from "react";

const SITE_NAME = "FindSolarInstallers.xyz";

export function usePageTitle(title: string) {
  useEffect(() => {
    document.title = title ? `${title} | ${SITE_NAME}` : `Find Solar Installers Near You | ${SITE_NAME}`;
    return () => {
      document.title = `Find Solar Installers Near You | ${SITE_NAME}`;
    };
  }, [title]);
}

export function useJsonLd(data: Record<string, unknown> | null) {
  useEffect(() => {
    if (!data) return;
    const script = document.createElement("script");
    script.type = "application/ld+json";
    script.textContent = JSON.stringify(data);
    script.id = "json-ld-seo";
    // Remove any existing
    const existing = document.getElementById("json-ld-seo");
    if (existing) existing.remove();
    document.head.appendChild(script);
    return () => {
      script.remove();
    };
  }, [data]);
}
