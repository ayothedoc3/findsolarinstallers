import { api } from "./api";

interface User {
  id: number;
  email: string;
  role: string;
  first_name: string | null;
  last_name: string | null;
  company_name: string | null;
  phone: string | null;
  is_active: boolean;
}

interface TokenResponse {
  access_token: string;
  refresh_token: string;
  token_type: string;
}

export async function login(email: string, password: string): Promise<User> {
  const tokens = await api.post<TokenResponse>("/auth/login", { email, password });
  api.setToken(tokens.access_token);
  localStorage.setItem("refresh_token", tokens.refresh_token);
  return getMe();
}

export async function register(data: Record<string, string>): Promise<User> {
  await api.post("/auth/register", data);
  return login(data.email, data.password);
}

export async function getMe(): Promise<User> {
  return api.get<User>("/auth/me");
}

export function logout() {
  api.setToken(null);
  localStorage.removeItem("refresh_token");
  window.location.href = "/login";
}

export function isAuthenticated(): boolean {
  return !!api.getToken();
}

export function getUserRole(): string | null {
  const token = api.getToken();
  if (!token) return null;
  try {
    const payload = JSON.parse(atob(token.split(".")[1]));
    return payload.role || null;
  } catch {
    return null;
  }
}
