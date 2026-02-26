import { createRoute } from "@tanstack/react-router";
import { adminLayoutRoute } from "./admin";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { useState, useEffect } from "react";
import {
  Eye, Users, Clock, TrendingDown, Globe, MousePointer,
  ExternalLink, Radio,
} from "lucide-react";

// ─── Route ──────────────────────────────────────────────────────────────────

export const adminAnalyticsRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/analytics",
  component: AdminAnalytics,
});

// ─── Types ──────────────────────────────────────────────────────────────────

type Tab = "overview" | "acquisition" | "pages" | "live";

interface OverviewData {
  total_pageviews: number;
  unique_visitors: number;
  bounce_rate: number;
  avg_duration_ms: number | null;
  daily: { date: string; views: number; visitors: number }[];
  top_pages: { path: string; views: number }[];
  top_referrers: { domain: string; views: number }[];
}

interface AcquisitionData {
  sources: Record<string, number>;
  campaigns: { source: string; medium: string | null; campaign: string | null; views: number }[];
  referrers: { domain: string; views: number }[];
}

interface PagesData {
  pages: {
    path: string;
    views: number;
    unique_visitors: number;
    avg_duration_ms: number | null;
    bounce_rate: number;
  }[];
}

interface LiveData {
  active_visitors: number;
  visitors: { session_id: string; path: string; seconds_ago: number }[];
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function formatDuration(ms: number | null): string {
  if (!ms) return "—";
  const sec = Math.round(ms / 1000);
  if (sec < 60) return `${sec}s`;
  return `${Math.floor(sec / 60)}m ${sec % 60}s`;
}

function formatNumber(n: number): string {
  if (n >= 1000000) return `${(n / 1000000).toFixed(1)}M`;
  if (n >= 1000) return `${(n / 1000).toFixed(1)}K`;
  return n.toString();
}

// ─── Component ──────────────────────────────────────────────────────────────

function AdminAnalytics() {
  const [tab, setTab] = useState<Tab>("overview");
  const [days, setDays] = useState(7);

  const tabs: { id: Tab; label: string; icon: typeof Eye }[] = [
    { id: "overview", label: "Overview", icon: Eye },
    { id: "acquisition", label: "Acquisition", icon: Globe },
    { id: "pages", label: "Pages", icon: MousePointer },
    { id: "live", label: "Live", icon: Radio },
  ];

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="font-heading text-2xl font-bold">Analytics</h1>
        {tab !== "live" && (
          <div className="flex items-center gap-1 bg-white border border-border rounded-lg p-1">
            {[7, 30, 90].map((d) => (
              <button
                key={d}
                onClick={() => setDays(d)}
                className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors ${
                  days === d ? "bg-accent text-white" : "text-muted-foreground hover:text-foreground"
                }`}
              >
                {d}d
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Tab bar */}
      <div className="flex gap-1 bg-white border border-border rounded-lg p-1 mb-6">
        {tabs.map((t) => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={`flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors flex-1 justify-center ${
              tab === t.id ? "bg-accent text-white" : "text-muted-foreground hover:text-foreground"
            }`}
          >
            <t.icon className="w-4 h-4" />
            {t.label}
          </button>
        ))}
      </div>

      {tab === "overview" && <OverviewTab days={days} />}
      {tab === "acquisition" && <AcquisitionTab days={days} />}
      {tab === "pages" && <PagesTab days={days} />}
      {tab === "live" && <LiveTab />}
    </div>
  );
}

// ─── Overview Tab ───────────────────────────────────────────────────────────

function OverviewTab({ days }: { days: number }) {
  const { data } = useQuery<OverviewData>({
    queryKey: ["admin", "analytics", "overview", days],
    queryFn: () => api.get(`/admin/analytics/overview?days=${days}`),
  });

  if (!data) return <LoadingSkeleton />;

  const maxViews = Math.max(...(data.daily.map((d) => d.views)), 1);

  const statCards = [
    { label: "Pageviews", value: formatNumber(data.total_pageviews), icon: Eye },
    { label: "Unique Visitors", value: formatNumber(data.unique_visitors), icon: Users },
    { label: "Bounce Rate", value: `${data.bounce_rate}%`, icon: TrendingDown },
    { label: "Avg Duration", value: formatDuration(data.avg_duration_ms), icon: Clock },
  ];

  return (
    <div className="space-y-6">
      {/* Stat cards */}
      <div className="grid sm:grid-cols-4 gap-4">
        {statCards.map((s) => (
          <div key={s.label} className="bg-white rounded-xl border border-border p-5">
            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
              <s.icon className="w-4 h-4" /> {s.label}
            </div>
            <div className="text-2xl font-bold font-heading">{s.value}</div>
          </div>
        ))}
      </div>

      {/* Chart */}
      <div className="bg-white rounded-xl border border-border p-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">
          Pageviews ({days}d)
        </h3>
        {data.daily.length === 0 ? (
          <p className="text-sm text-muted-foreground py-8 text-center">No data yet. Pageviews will appear here once tracking starts.</p>
        ) : (
          <div className="flex items-end gap-1" style={{ height: 160 }}>
            {data.daily.map((d) => (
              <div key={d.date} className="flex-1 flex flex-col items-center gap-1">
                <span className="text-[10px] text-muted-foreground">{d.views}</span>
                <div
                  className="w-full bg-accent/80 rounded-t transition-all hover:bg-accent"
                  style={{ height: `${(d.views / maxViews) * 120}px`, minHeight: d.views > 0 ? 4 : 0 }}
                  title={`${d.date}: ${d.views} views, ${d.visitors} visitors`}
                />
                <span className="text-[9px] text-muted-foreground">
                  {new Date(d.date + "T00:00:00").toLocaleDateString("en", { month: "short", day: "numeric" })}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Top pages & referrers */}
      <div className="grid md:grid-cols-2 gap-6">
        <div className="bg-white rounded-xl border border-border p-6">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Top Pages</h3>
          {data.top_pages.length === 0 ? (
            <p className="text-sm text-muted-foreground">No data yet</p>
          ) : (
            <div className="space-y-2">
              {data.top_pages.map((p) => (
                <div key={p.path} className="flex items-center justify-between text-sm">
                  <span className="truncate mr-2 text-foreground">{p.path}</span>
                  <span className="text-muted-foreground font-medium whitespace-nowrap">{p.views}</span>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="bg-white rounded-xl border border-border p-6">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Top Referrers</h3>
          {data.top_referrers.length === 0 ? (
            <p className="text-sm text-muted-foreground">No referral traffic yet</p>
          ) : (
            <div className="space-y-2">
              {data.top_referrers.map((r) => (
                <div key={r.domain} className="flex items-center justify-between text-sm">
                  <span className="truncate mr-2 text-foreground flex items-center gap-1">
                    <ExternalLink className="w-3 h-3 text-muted-foreground" />
                    {r.domain}
                  </span>
                  <span className="text-muted-foreground font-medium whitespace-nowrap">{r.views}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ─── Acquisition Tab ────────────────────────────────────────────────────────

function AcquisitionTab({ days }: { days: number }) {
  const { data } = useQuery<AcquisitionData>({
    queryKey: ["admin", "analytics", "acquisition", days],
    queryFn: () => api.get(`/admin/analytics/acquisition?days=${days}`),
  });

  if (!data) return <LoadingSkeleton />;

  const sourceLabels: Record<string, { label: string; color: string }> = {
    direct: { label: "Direct", color: "bg-blue-500" },
    organic: { label: "Organic Search", color: "bg-green-500" },
    referral: { label: "Referral", color: "bg-purple-500" },
    social: { label: "Social", color: "bg-pink-500" },
  };

  const totalViews = Object.values(data.sources).reduce((a, b) => a + b, 0) || 1;

  return (
    <div className="space-y-6">
      {/* Source breakdown */}
      <div className="bg-white rounded-xl border border-border p-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Traffic Sources</h3>
        {totalViews <= 1 && Object.keys(data.sources).length === 0 ? (
          <p className="text-sm text-muted-foreground">No traffic data yet</p>
        ) : (
          <>
            {/* Stacked bar */}
            <div className="flex rounded-full overflow-hidden h-4 mb-4">
              {Object.entries(data.sources).map(([key, count]) => (
                <div
                  key={key}
                  className={`${sourceLabels[key]?.color || "bg-gray-400"} transition-all`}
                  style={{ width: `${(count / totalViews) * 100}%` }}
                  title={`${sourceLabels[key]?.label || key}: ${count}`}
                />
              ))}
            </div>
            <div className="grid sm:grid-cols-4 gap-3">
              {Object.entries(data.sources).map(([key, count]) => (
                <div key={key} className="flex items-center gap-2 text-sm">
                  <div className={`w-3 h-3 rounded-full ${sourceLabels[key]?.color || "bg-gray-400"}`} />
                  <span className="text-foreground">{sourceLabels[key]?.label || key}</span>
                  <span className="text-muted-foreground ml-auto font-medium">
                    {count} ({Math.round((count / totalViews) * 100)}%)
                  </span>
                </div>
              ))}
            </div>
          </>
        )}
      </div>

      {/* UTM Campaigns */}
      <div className="bg-white rounded-xl border border-border p-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">UTM Campaigns</h3>
        {data.campaigns.length === 0 ? (
          <p className="text-sm text-muted-foreground">No UTM-tagged traffic yet</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left py-2 text-muted-foreground font-medium">Source</th>
                  <th className="text-left py-2 text-muted-foreground font-medium">Medium</th>
                  <th className="text-left py-2 text-muted-foreground font-medium">Campaign</th>
                  <th className="text-right py-2 text-muted-foreground font-medium">Views</th>
                </tr>
              </thead>
              <tbody>
                {data.campaigns.map((c, i) => (
                  <tr key={i} className="border-b border-border/50">
                    <td className="py-2">{c.source}</td>
                    <td className="py-2 text-muted-foreground">{c.medium || "—"}</td>
                    <td className="py-2 text-muted-foreground">{c.campaign || "—"}</td>
                    <td className="py-2 text-right font-medium">{c.views}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Top referrers */}
      <div className="bg-white rounded-xl border border-border p-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Referrer Domains</h3>
        {data.referrers.length === 0 ? (
          <p className="text-sm text-muted-foreground">No referral traffic yet</p>
        ) : (
          <div className="space-y-2">
            {data.referrers.map((r) => {
              const pct = totalViews > 0 ? (r.views / totalViews) * 100 : 0;
              return (
                <div key={r.domain} className="relative">
                  <div className="absolute inset-0 bg-accent/5 rounded" style={{ width: `${pct}%` }} />
                  <div className="relative flex items-center justify-between py-1.5 px-2 text-sm">
                    <span className="flex items-center gap-1">
                      <ExternalLink className="w-3 h-3 text-muted-foreground" />
                      {r.domain}
                    </span>
                    <span className="text-muted-foreground font-medium">{r.views}</span>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

// ─── Pages Tab ──────────────────────────────────────────────────────────────

function PagesTab({ days }: { days: number }) {
  const { data } = useQuery<PagesData>({
    queryKey: ["admin", "analytics", "pages", days],
    queryFn: () => api.get(`/admin/analytics/pages?days=${days}`),
  });

  if (!data) return <LoadingSkeleton />;

  return (
    <div className="bg-white rounded-xl border border-border p-6">
      <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">
        Page Performance ({days}d)
      </h3>
      {data.pages.length === 0 ? (
        <p className="text-sm text-muted-foreground py-4 text-center">No page data yet</p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-2 text-muted-foreground font-medium">Page</th>
                <th className="text-right py-2 text-muted-foreground font-medium">Views</th>
                <th className="text-right py-2 text-muted-foreground font-medium">Unique</th>
                <th className="text-right py-2 text-muted-foreground font-medium">Avg Duration</th>
                <th className="text-right py-2 text-muted-foreground font-medium">Bounce</th>
              </tr>
            </thead>
            <tbody>
              {data.pages.map((p) => (
                <tr key={p.path} className="border-b border-border/50 hover:bg-muted/30">
                  <td className="py-2 max-w-xs truncate" title={p.path}>{p.path}</td>
                  <td className="py-2 text-right font-medium">{p.views}</td>
                  <td className="py-2 text-right text-muted-foreground">{p.unique_visitors}</td>
                  <td className="py-2 text-right text-muted-foreground">{formatDuration(p.avg_duration_ms)}</td>
                  <td className="py-2 text-right text-muted-foreground">{p.bounce_rate}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

// ─── Live Tab ───────────────────────────────────────────────────────────────

function LiveTab() {
  const { data, dataUpdatedAt } = useQuery<LiveData>({
    queryKey: ["admin", "analytics", "live"],
    queryFn: () => api.get("/admin/analytics/live"),
    refetchInterval: 10000,
  });

  // Pulse animation timestamp
  const [pulse, setPulse] = useState(false);
  useEffect(() => {
    setPulse(true);
    const t = setTimeout(() => setPulse(false), 500);
    return () => clearTimeout(t);
  }, [dataUpdatedAt]);

  if (!data) return <LoadingSkeleton />;

  return (
    <div className="space-y-6">
      {/* Big live counter */}
      <div className="bg-white rounded-xl border border-border p-8 text-center">
        <div className="flex items-center justify-center gap-3 mb-2">
          <div className={`w-3 h-3 rounded-full bg-green-500 ${pulse ? "animate-ping" : ""}`} />
          <span className="text-sm text-muted-foreground uppercase tracking-wider font-semibold">Live Visitors</span>
        </div>
        <div className="text-6xl font-bold font-heading text-foreground">{data.active_visitors}</div>
        <p className="text-sm text-muted-foreground mt-2">Active in the last 30 seconds</p>
      </div>

      {/* Visitor list */}
      <div className="bg-white rounded-xl border border-border p-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">
          Current Pages
        </h3>
        {data.visitors.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">No active visitors right now</p>
        ) : (
          <div className="space-y-2">
            {data.visitors.map((v, i) => (
              <div key={i} className="flex items-center justify-between py-2 px-3 bg-muted/30 rounded-lg text-sm">
                <div className="flex items-center gap-3">
                  <div className="w-2 h-2 rounded-full bg-green-500" />
                  <span className="font-mono text-xs text-muted-foreground">{v.session_id}</span>
                  <span className="text-foreground">{v.path}</span>
                </div>
                <span className="text-muted-foreground text-xs">{v.seconds_ago}s ago</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

// ─── Shared ─────────────────────────────────────────────────────────────────

function LoadingSkeleton() {
  return (
    <div className="space-y-4">
      <div className="grid sm:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white rounded-xl border border-border p-5 animate-pulse">
            <div className="h-4 bg-muted rounded w-20 mb-2" />
            <div className="h-8 bg-muted rounded w-16" />
          </div>
        ))}
      </div>
      <div className="bg-white rounded-xl border border-border p-6 animate-pulse">
        <div className="h-4 bg-muted rounded w-32 mb-4" />
        <div className="h-40 bg-muted/50 rounded" />
      </div>
    </div>
  );
}
