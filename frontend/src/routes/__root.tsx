import { createRootRouteWithContext, Outlet, useRouterState } from "@tanstack/react-router";
import type { QueryClient } from "@tanstack/react-query";
import { useEffect } from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { trackPageview } from "@/lib/tracker";

interface RouterContext {
  queryClient: QueryClient;
}

export const rootRoute = createRootRouteWithContext<RouterContext>()({
  component: RootLayout,
});

function RootLayout() {
  const { location } = useRouterState();

  useEffect(() => {
    trackPageview(location.pathname);
  }, [location.pathname]);

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
