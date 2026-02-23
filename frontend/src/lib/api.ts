const API_BASE = "/api";

class ApiClient {
  private token: string | null = null;
  private refreshPromise: Promise<string | null> | null = null;

  constructor() {
    this.token = localStorage.getItem("access_token");
  }

  setToken(token: string | null) {
    this.token = token;
    if (token) {
      localStorage.setItem("access_token", token);
    } else {
      localStorage.removeItem("access_token");
    }
  }

  getToken() {
    return this.token;
  }

  private clearAuth() {
    this.setToken(null);
    localStorage.removeItem("refresh_token");
  }

  private async refreshAccessToken(): Promise<string | null> {
    const refreshToken = localStorage.getItem("refresh_token");
    if (!refreshToken) {
      this.clearAuth();
      return null;
    }

    if (!this.refreshPromise) {
      this.refreshPromise = (async () => {
        const res = await fetch(`${API_BASE}/auth/refresh`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ refresh_token: refreshToken }),
        });

        if (!res.ok) {
          this.clearAuth();
          return null;
        }

        const body = await res.json().catch(() => null);
        const accessToken = body?.access_token;
        const nextRefreshToken = body?.refresh_token;

        if (!accessToken || !nextRefreshToken) {
          this.clearAuth();
          return null;
        }

        this.setToken(accessToken);
        localStorage.setItem("refresh_token", nextRefreshToken);
        return accessToken as string;
      })().finally(() => {
        this.refreshPromise = null;
      });
    }

    return this.refreshPromise;
  }

  private async request<T>(path: string, options: RequestInit = {}, allowRefresh = true): Promise<T> {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      ...(options.headers as Record<string, string>),
    };

    if (this.token) {
      headers["Authorization"] = `Bearer ${this.token}`;
    }

    const res = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers,
    });

    if (
      res.status === 401 &&
      allowRefresh &&
      path !== "/auth/login" &&
      path !== "/auth/refresh"
    ) {
      const refreshedToken = await this.refreshAccessToken();
      if (refreshedToken) {
        return this.request<T>(path, options, false);
      }
    }

    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      throw new Error(body.detail || `HTTP ${res.status}`);
    }

    if (res.status === 204) return undefined as T;
    return res.json();
  }

  get<T>(path: string) {
    return this.request<T>(path);
  }

  post<T>(path: string, data?: unknown) {
    return this.request<T>(path, { method: "POST", body: JSON.stringify(data) });
  }

  put<T>(path: string, data?: unknown) {
    return this.request<T>(path, { method: "PUT", body: JSON.stringify(data) });
  }

  delete<T>(path: string) {
    return this.request<T>(path, { method: "DELETE" });
  }
}

export const api = new ApiClient();
