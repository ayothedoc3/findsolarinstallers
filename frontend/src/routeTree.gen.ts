import { rootRoute } from "./routes/__root";
import { indexRoute } from "./routes/index";
import { searchRoute } from "./routes/search";
import { categoriesRoute } from "./routes/categories";
import { aboutRoute } from "./routes/about";
import { contactRoute } from "./routes/contact";
import { privacyRoute } from "./routes/privacy";
import { forInstallersRoute } from "./routes/for-installers";
import { loginRoute } from "./routes/login";
import { registerRoute } from "./routes/register";
import { listingDetailRoute } from "./routes/listing.$slug";
import {
  adminLayoutRoute, adminIndexRoute, adminApiKeysRoute, adminPipelineRoute,
  adminListingsRoute, adminUsersRoute, adminCategoriesRoute, adminPlansRoute,
  adminSettingsRoute, adminClaimsRoute,
} from "./routes/admin";
import { adminAnalyticsRoute } from "./routes/admin-analytics";
import { adminInstallerInquiriesRoute } from "./routes/admin-inquiries";
import { dashboardLayoutRoute, dashboardIndexRoute, dashboardListingsRoute, dashboardLeadsRoute, dashboardProfileRoute } from "./routes/dashboard";

export const routeTree = rootRoute.addChildren([
  indexRoute,
  searchRoute,
  categoriesRoute,
  aboutRoute,
  contactRoute,
  privacyRoute,
  forInstallersRoute,
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
    adminClaimsRoute,
    adminInstallerInquiriesRoute,
    adminAnalyticsRoute,
    adminSettingsRoute,
  ]),
  dashboardLayoutRoute.addChildren([
    dashboardIndexRoute,
    dashboardListingsRoute,
    dashboardLeadsRoute,
    dashboardProfileRoute,
  ]),
]);
