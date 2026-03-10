import { createRoute } from "@tanstack/react-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CheckCircle2, Search, XCircle } from "lucide-react";
import { useState } from "react";

import { adminLayoutRoute } from "./admin";
import { api } from "@/lib/api";

export const adminInstallerInquiriesRoute = createRoute({
  getParentRoute: () => adminLayoutRoute,
  path: "/installer-inquiries",
  component: AdminInstallerInquiries,
});

function AdminInstallerInquiries() {
  const queryClient = useQueryClient();
  const [statusFilter, setStatusFilter] = useState("new");
  const [search, setSearch] = useState("");

  const { data: inquiries = [] } = useQuery<any[]>({
    queryKey: ["admin", "installer-inquiries", statusFilter, search],
    queryFn: () => {
      const params = new URLSearchParams();
      if (statusFilter) params.set("status", statusFilter);
      if (search) params.set("q", search);
      return api.get(`/admin/installer-inquiries?${params.toString()}`);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/installer-inquiries/${id}`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "installer-inquiries"] }),
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6 gap-4">
        <h1 className="font-heading text-2xl font-bold">Installer Inquiries</h1>
        <div className="flex items-center gap-3">
          <div className="relative">
            <Search className="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search business or email..."
              className="pl-9 pr-3 py-2 rounded-lg border border-border bg-white text-sm"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-2 rounded-lg border border-border bg-white text-sm"
          >
            <option value="new">New</option>
            <option value="verified">Verified</option>
            <option value="closed">Closed</option>
            <option value="rejected">Rejected</option>
            <option value="">All</option>
          </select>
        </div>
      </div>

      {inquiries.length === 0 ? (
        <div className="bg-white rounded-xl border border-border p-8 text-center text-muted-foreground">
          No installer inquiries found.
        </div>
      ) : (
        <div className="space-y-4">
          {inquiries.map((inquiry) => (
            <div key={inquiry.id} className="bg-white rounded-xl border border-border p-5">
              <div className="flex items-start justify-between gap-4">
                <div className="space-y-2">
                  <div className="flex items-center gap-2">
                    <h2 className="font-semibold">{inquiry.business_name}</h2>
                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                      inquiry.status === "new" ? "bg-orange-50 text-orange-700" :
                      inquiry.status === "verified" ? "bg-blue-50 text-blue-700" :
                      inquiry.status === "closed" ? "bg-green-50 text-green-700" :
                      "bg-red-50 text-red-700"
                    }`}>
                      {inquiry.status}
                    </span>
                  </div>
                  <div className="text-sm text-muted-foreground">
                    {inquiry.name} · {inquiry.email}
                    {inquiry.phone ? ` · ${inquiry.phone}` : ""}
                    {inquiry.state ? ` · ${inquiry.state}` : ""}
                  </div>
                  {(inquiry.listing_name || inquiry.listing_slug) && (
                    <div className="text-sm text-muted-foreground">
                      Listing: {inquiry.listing_name || inquiry.listing_slug}
                    </div>
                  )}
                  {inquiry.notes && (
                    <p className="text-sm text-foreground whitespace-pre-line">{inquiry.notes}</p>
                  )}
                  <div className="text-xs text-muted-foreground">
                    Source: {inquiry.source_path}
                    {inquiry.utm_source ? ` · ${inquiry.utm_source}/${inquiry.utm_medium || "n/a"}` : ""}
                  </div>
                </div>

                <div className="flex flex-col gap-2 shrink-0">
                  {inquiry.status !== "verified" && (
                    <button
                      onClick={() => updateMutation.mutate({ id: inquiry.id, status: "verified" })}
                      className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                      <CheckCircle2 className="w-4 h-4" /> Verify
                    </button>
                  )}
                  {inquiry.status !== "closed" && (
                    <button
                      onClick={() => updateMutation.mutate({ id: inquiry.id, status: "closed" })}
                      className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700"
                    >
                      <CheckCircle2 className="w-4 h-4" /> Close
                    </button>
                  )}
                  {inquiry.status !== "rejected" && (
                    <button
                      onClick={() => updateMutation.mutate({ id: inquiry.id, status: "rejected" })}
                      className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                    >
                      <XCircle className="w-4 h-4" /> Reject
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
