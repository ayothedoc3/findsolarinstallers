import { rootRoute } from "./routes/__root";
import { indexRoute } from "./routes/index";
import { searchRoute } from "./routes/search";
import { loginRoute } from "./routes/login";
import { registerRoute } from "./routes/register";
import { listingDetailRoute } from "./routes/listing.$slug";
import { adminLayoutRoute, adminIndexRoute, adminApiKeysRoute, adminPipelineRoute, adminListingsRoute, adminUsersRoute } from "./routes/admin";
import { dashboardLayoutRoute, dashboardIndexRoute, dashboardListingsRoute, dashboardLeadsRoute, dashboardProfileRoute } from "./routes/dashboard";

export const routeTree = rootRoute.addChildren([
  indexRoute,
  searchRoute,
  loginRoute,
  registerRoute,
  listingDetailRoute,
  adminLayoutRoute.addChildren([
    adminIndexRoute,
    adminApiKeysRoute,
    adminPipelineRoute,
    adminListingsRoute,
    adminUsersRoute,
  ]),
  dashboardLayoutRoute.addChildren([
    dashboardIndexRoute,
    dashboardListingsRoute,
    dashboardLeadsRoute,
    dashboardProfileRoute,
  ]),
]);
