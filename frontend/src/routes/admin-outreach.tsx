import { createRoute } from "@tanstack/react-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CheckCircle2, Download, Send } from "lucide-react";
import { useEffect, useState } from "react";

import { adminLayoutRoute } from "./admin";
import { api } from "@/lib/api";
import { useMarketplaceData } from "@/lib/marketplace";

export const adminOutreachRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/outreach",
  component: AdminOutreach,
});

interface Target {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  website: string | null;
  city: string | null;
  state: string | null;
  slug: string;
  listing_url: string;
  google_rating: number | null;
  total_reviews: number;
  outreach_sent: boolean;
}

interface TargetsResponse {
  items: Target[];
  total: number;
  total_with_email: number;
  total_sent: number;
  page: number;
  per_page: number;
  state: string;
}

function AdminOutreach() {
  const queryClient = useQueryClient();
  const { data: marketplace, isLoading: marketplaceLoading } = useMarketplaceData();
  const launchState = marketplace?.launch_state ?? "";

  const [filter, setFilter] = useState("unsent");
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [featuredUrl, setFeaturedUrl] = useState("");
  const [page, setPage] = useState(1);

  useEffect(() => {
    setSelected(new Set());
  }, [filter, page, launchState]);

  const { data, isLoading } = useQuery<TargetsResponse>({
    queryKey: ["admin", "outreach-targets", launchState, filter, page],
    enabled: Boolean(launchState),
    queryFn: () =>
      api.get(
        `/admin/outreach/targets?state=${encodeURIComponent(launchState)}&filter=${filter}&page=${page}&per_page=50`
      ),
  });

  const marketName = data?.state || launchState || "launch market";
  const targets = data?.items ?? [];
  const total = data?.total ?? 0;
  const totalWithEmail = data?.total_with_email ?? 0;
  const totalSent = data?.total_sent ?? 0;
  const remaining = Math.max(0, totalWithEmail - totalSent);

  const sendMutation = useMutation({
    mutationFn: (ids: number[]) =>
      api.post<{ sent: number; failed: number; skipped: number }>("/admin/outreach/send", {
        listing_ids: ids,
        featured_example_url: featuredUrl || null,
      }),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ["admin", "outreach-targets"] });
      setSelected(new Set());
      alert(`Sent: ${result.sent}, Failed: ${result.failed}, Skipped: ${result.skipped}`);
    },
    onError: (err: Error) => alert(err.message),
  });

  const toggleAll = () => {
    if (selected.size === targets.length) {
      setSelected(new Set());
    } else {
      setSelected(new Set(targets.map((target) => target.id)));
    }
  };

  const toggle = (id: number) => {
    const next = new Set(selected);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelected(next);
  };

  const exportCsv = async () => {
    const token = api.getToken();
    if (!token) {
      alert("You must be signed in as an admin to export CSV.");
      return;
    }
    if (!launchState) {
      alert("Launch state is still loading.");
      return;
    }

    const response = await fetch(`/api/admin/outreach/export?state=${encodeURIComponent(launchState)}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      const body = await response.json().catch(() => ({}));
      alert(body.detail || `HTTP ${response.status}`);
      return;
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    const disposition = response.headers.get("Content-Disposition");
    const filenameMatch = disposition?.match(/filename="([^"]+)"/);
    link.href = url;
    link.download = filenameMatch?.[1] || `installers-${launchState.toLowerCase().replace(/\s+/g, "-")}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  };

  const pageCount = total > 0 ? Math.ceil(total / 50) : 0;

  return (
    <div>
      <div className="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <div>
          <h1 className="font-heading text-2xl font-bold">Outreach</h1>
          <p className="text-sm text-muted-foreground mt-1">
            {marketName}: {totalWithEmail} installers with email, {totalSent} already contacted, {remaining} remaining
          </p>
        </div>
        <div className="flex items-center gap-3">
          <select
            value={filter}
            onChange={(e) => {
              setFilter(e.target.value);
              setPage(1);
            }}
            className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
          >
            <option value="unsent">Not contacted</option>
            <option value="sent">Already sent</option>
            <option value="all">All</option>
          </select>
          <button
            onClick={() => void exportCsv()}
            disabled={!launchState}
            className="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm font-medium hover:bg-gray-50 disabled:opacity-50"
          >
            <Download className="w-4 h-4" /> Export CSV
          </button>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-border p-4 mb-4">
        <label className="block text-sm font-medium mb-1">Featured example URL (optional)</label>
        <input
          value={featuredUrl}
          onChange={(e) => setFeaturedUrl(e.target.value)}
          placeholder="https://findsolarinstallers.xyz/listing/example-featured-installer"
          className="w-full px-3 py-2 rounded-lg border border-border text-sm"
        />
        <p className="text-xs text-muted-foreground mt-1">
          Include a featured listing so prospects can see the before-and-after upgrade.
        </p>
      </div>

      {selected.size > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 flex items-center justify-between gap-4">
          <span className="text-sm font-medium text-blue-900">
            {selected.size} installer{selected.size !== 1 ? "s" : ""} selected
          </span>
          <button
            onClick={() => sendMutation.mutate(Array.from(selected))}
            disabled={sendMutation.isPending}
            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <Send className="w-4 h-4" />
            {sendMutation.isPending ? "Sending..." : `Send outreach to ${selected.size}`}
          </button>
        </div>
      )}

      {marketplaceLoading || (isLoading && !data) ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          Loading...
        </div>
      ) : targets.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          No installers found matching this filter.
        </div>
      ) : (
        <>
          <div className="bg-white rounded-xl border border-border overflow-hidden">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-gray-50">
                  <th className="px-4 py-3 text-left">
                    <input
                      type="checkbox"
                      checked={targets.length > 0 && selected.size === targets.length}
                      onChange={toggleAll}
                    />
                  </th>
                  <th className="px-4 py-3 text-left font-medium">Business</th>
                  <th className="px-4 py-3 text-left font-medium">Email</th>
                  <th className="px-4 py-3 text-left font-medium">City</th>
                  <th className="px-4 py-3 text-left font-medium">Rating</th>
                  <th className="px-4 py-3 text-left font-medium">Reviews</th>
                  <th className="px-4 py-3 text-left font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                {targets.map((target) => (
                  <tr key={target.id} className="border-b border-border hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <input
                        type="checkbox"
                        checked={selected.has(target.id)}
                        onChange={() => toggle(target.id)}
                      />
                    </td>
                    <td className="px-4 py-3">
                      <a
                        href={target.listing_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:underline font-medium"
                      >
                        {target.name}
                      </a>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{target.email}</td>
                    <td className="px-4 py-3 text-muted-foreground">
                      {[target.city, target.state].filter(Boolean).join(", ") || "-"}
                    </td>
                    <td className="px-4 py-3">{target.google_rating ?? "-"}</td>
                    <td className="px-4 py-3">{target.total_reviews}</td>
                    <td className="px-4 py-3">
                      {target.outreach_sent ? (
                        <span className="inline-flex items-center gap-1 text-green-700 text-xs font-medium">
                          <CheckCircle2 className="w-3 h-3" /> Sent
                        </span>
                      ) : (
                        <span className="text-xs text-muted-foreground">Not contacted</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {pageCount > 1 && (
            <div className="flex items-center justify-between mt-4">
              <span className="text-sm text-muted-foreground">
                Page {page} of {pageCount}
              </span>
              <div className="flex gap-2">
                <button
                  disabled={page <= 1}
                  onClick={() => setPage(page - 1)}
                  className="px-3 py-1 rounded border text-sm disabled:opacity-50"
                >
                  Prev
                </button>
                <button
                  disabled={page >= pageCount}
                  onClick={() => setPage(page + 1)}
                  className="px-3 py-1 rounded border text-sm disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
