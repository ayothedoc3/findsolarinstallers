import { rootRoute } from "./routes/__root";
import { indexRoute } from "./routes/index";
import { searchRoute } from "./routes/search";
import { categoriesRoute } from "./routes/categories";
import { loginRoute } from "./routes/login";
import { registerRoute } from "./routes/register";
import { listingDetailRoute } from "./routes/listing.$slug";
import {
  adminLayoutRoute, adminIndexRoute, adminApiKeysRoute, adminPipelineRoute,
  adminListingsRoute, adminUsersRoute, adminCategoriesRoute, adminPlansRoute,
  adminSettingsRoute,
} from "./routes/admin";
import { dashboardLayoutRoute, dashboardIndexRoute, dashboardListingsRoute, dashboardLeadsRoute, dashboardProfileRoute } from "./routes/dashboard";

export const routeTree = rootRoute.addChildren([
  indexRoute,
  searchRoute,
  categoriesRoute,
  loginRoute,
  registerRoute,
  listingDetailRoute,
  adminLayoutRoute.addChildren([
    adminIndexRoute,
    adminApiKeysRoute,
    adminPipelineRoute,
    adminListingsRoute,
    adminUsersRoute,
    adminCategoriesRoute,
    adminPlansRoute,
    adminSettingsRoute,
  ]),
  dashboardLayoutRoute.addChildren([
    dashboardIndexRoute,
    dashboardListingsRoute,
    dashboardLeadsRoute,
    dashboardProfileRoute,
  ]),
]);
