import { createRoute, useNavigate } from "@tanstack/react-router";
import { rootRoute } from "./__root";
import { useState } from "react";
import { Link } from "@tanstack/react-router";
import { register } from "@/lib/auth";
import { usePageTitle } from "@/lib/seo";

export const registerRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/register",
  component: RegisterPage,
});

function RegisterPage() {
  usePageTitle("Create Account");
  const [formData, setFormData] = useState({
    email: "", password: "", first_name: "", last_name: "",
    company_name: "", phone: "", role: "business_owner",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const update = (key: string, value: string) =>
    setFormData((prev) => ({ ...prev, [key]: value }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await register(formData);
      navigate({ to: "/dashboard" });
    } catch (err: any) {
      setError(err.message || "Registration failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-md mx-auto px-4 py-16">
      <div className="bg-white rounded-xl border border-border p-8">
        <h1 className="font-heading text-2xl font-bold text-center mb-2">
          Create Account
        </h1>
        <p className="text-center text-muted-foreground mb-8">
          Use an owner account after your profile has been reviewed or claimed
        </p>

        {error && (
          <div className="bg-destructive/10 text-destructive text-sm px-4 py-3 rounded-lg mb-4">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">First Name</label>
              <input
                type="text"
                value={formData.first_name}
                onChange={(e) => update("first_name", e.target.value)}
                className="w-full px-3 py-2 rounded-lg border border-border bg-background"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Last Name</label>
              <input
                type="text"
                value={formData.last_name}
                onChange={(e) => update("last_name", e.target.value)}
                className="w-full px-3 py-2 rounded-lg border border-border bg-background"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Company Name</label>
            <input
              type="text"
              value={formData.company_name}
              onChange={(e) => update("company_name", e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Email</label>
            <input
              type="email"
              value={formData.email}
              onChange={(e) => update("email", e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Phone</label>
            <input
              type="tel"
              value={formData.phone}
              onChange={(e) => update("phone", e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Password</label>
            <input
              type="password"
              value={formData.password}
              onChange={(e) => update("password", e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-border bg-background"
              required
              minLength={8}
            />
          </div>
          <button
            type="submit"
            disabled={loading}
            className="w-full bg-accent hover:bg-accent/90 text-accent-foreground font-semibold py-3 rounded-lg transition-colors disabled:opacity-50"
          >
            {loading ? "Creating account..." : "Create Owner Account"}
          </button>
        </form>

        <p className="text-center text-sm text-muted-foreground mt-6">
          Already have an account?{" "}
          <Link to="/login" className="text-accent hover:underline font-medium">
            Sign In
          </Link>
        </p>
      </div>
    </div>
  );
}
