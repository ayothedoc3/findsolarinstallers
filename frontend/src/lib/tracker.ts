/**
 * Lightweight analytics tracker — sends pageview beacons to /api/t.
 * Privacy-first: no cookies, fingerprint-based sessions, respects DNT.
 */

let lastPath: string | null = null;
let lastTime: number | null = null;

function getUaHash(): string {
  const raw = `${navigator.userAgent}|${navigator.language}|${screen.width}x${screen.height}`;
  // Simple hash — no need for crypto, just needs to be consistent
  let hash = 0;
  for (let i = 0; i < raw.length; i++) {
    const chr = raw.charCodeAt(i);
    hash = ((hash << 5) - hash) + chr;
    hash |= 0;
  }
  return Math.abs(hash).toString(36);
}

function getUtmParams(): { utm_source?: string; utm_medium?: string; utm_campaign?: string } {
  const params = new URLSearchParams(window.location.search);
  const result: Record<string, string> = {};
  for (const key of ["utm_source", "utm_medium", "utm_campaign"] as const) {
    const val = params.get(key);
    if (val) result[key] = val;
  }
  return result;
}

function sendBeacon(data: Record<string, unknown>) {
  const body = JSON.stringify(data);
  if (navigator.sendBeacon) {
    navigator.sendBeacon("/api/t", new Blob([body], { type: "application/json" }));
  } else {
    fetch("/api/t", { method: "POST", body, headers: { "Content-Type": "application/json" }, keepalive: true }).catch(() => {});
  }
}

export function trackPageview(path: string) {
  // Respect Do Not Track
  if (navigator.doNotTrack === "1") return;

  // Skip admin pages — don't track admin's own browsing
  if (path.startsWith("/admin")) return;

  const now = Date.now();

  // Send duration for the previous page
  if (lastPath && lastTime && lastPath !== path) {
    sendBeacon({
      path: lastPath,
      duration_ms: now - lastTime,
      ua_hash: getUaHash(),
    });
  }

  // Send new pageview
  sendBeacon({
    path,
    referrer: document.referrer || undefined,
    screen_w: screen.width,
    screen_h: screen.height,
    ua_hash: getUaHash(),
    ...getUtmParams(),
  });

  lastPath = path;
  lastTime = now;
}

// Send duration on page unload
if (typeof window !== "undefined") {
  window.addEventListener("beforeunload", () => {
    if (lastPath && lastTime) {
      sendBeacon({
        path: lastPath,
        duration_ms: Date.now() - lastTime,
        ua_hash: getUaHash(),
      });
    }
  });
}
