-- ============================================================================
-- Solar Installation Directory Setup Script
-- Runs AFTER Flynax 4.10.1 default installation
-- Transforms generic classifieds into a Solar Installation Directory
-- ============================================================================
-- Database prefix {db_prefix} is replaced at runtime (same as dump.sql)
-- ============================================================================

-- ============================================================================
-- SECTION 1: Clean up default categories and related data
-- ============================================================================

-- Remove ALL default categories (books, cameras, clothing, motors, property, etc.)
DELETE FROM `{db_prefix}categories`;

-- Remove all listing relations (field-to-category mappings)
DELETE FROM `{db_prefix}listing_relations`;

-- Remove all listing title mappings
DELETE FROM `{db_prefix}listing_titles`;

-- Remove all search form relations
DELETE FROM `{db_prefix}search_forms_relations`;

-- Remove non-system search forms (keep listings_quick=15, listings_advanced=16,
-- listings_myads=26, listings_on_map=41)
DELETE FROM `{db_prefix}search_forms`
WHERE `ID` NOT IN (15, 16, 26, 41);

-- Remove default non-system listing fields
-- Keep system fields (ID < 0) and essential fields:
--   37=country, 191=country_level1, 192=country_level2,
--   82=description_add, 76=additional_information,
--   88=Category_ID, 107=keyword_search, 189=posted_by, 190=account_address_on_map
-- NOTE: Original title field (ID=81) is deleted; replaced by solar title field (ID=300)
DELETE FROM `{db_prefix}listing_fields`
WHERE `ID` NOT IN (-1, -2, -3, -4, 37, 191, 192, 82, 76, 88, 107, 189, 190)
  AND `ID` > 0;

-- Remove non-system listing groups (keep location group ID=21)
DELETE FROM `{db_prefix}listing_groups`
WHERE `ID` != 21;

-- Remove short form entries (default categories are gone)
DELETE FROM `{db_prefix}short_forms`;

-- Remove sorting form entries (default categories are gone)
DELETE FROM `{db_prefix}sorting_forms`;

-- Clean up lang keys for removed default categories
DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'categories+name+%' AND `Module` = 'category';
DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'categories+des+%' AND `Module` = 'category';

-- Clean up lang keys for removed default listing fields (keep system & retained fields)
-- NOTE: 'title' is removed here too; Section 10c re-inserts it as "Company Name"
DELETE FROM `{db_prefix}lang_keys`
WHERE `Key` LIKE 'listing_fields+name+%'
  AND `Key` NOT IN (
    'listing_fields+name+sf_status',
    'listing_fields+name+sf_active_till',
    'listing_fields+name+sf_plan',
    'listing_fields+name+sf_featured',
    'listing_fields+name+country',
    'listing_fields+name+country_level1',
    'listing_fields+name+country_level2',
    'listing_fields+name+description_add',
    'listing_fields+name+additional_information',
    'listing_fields+name+Category_ID',
    'listing_fields+name+keyword_search',
    'listing_fields+name+posted_by',
    'listing_fields+name+account_address_on_map'
  );

-- Clean up lang keys for removed default listing groups (keep location)
DELETE FROM `{db_prefix}lang_keys`
WHERE `Key` LIKE 'listing_groups+name+%'
  AND `Key` != 'listing_groups+name+location';

-- Clean up lang keys for removed default listing plans
DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'listing_plans+name+%';
DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'listing_plans+des+%';


-- ============================================================================
-- SECTION 2: Insert solar categories
-- ============================================================================
-- Using IDs starting at 2000 to avoid conflicts
-- Columns: (ID, Position, Path, Level, Tree, Parent_ID, Parent_IDs, Parent_keys,
--           Type, Key, Count, Lock, Add, Add_sub, Modified, Menu, Menu_icon, Status)

INSERT INTO `{db_prefix}categories`
  (`ID`, `Position`, `Path`, `Level`, `Tree`, `Parent_ID`, `Parent_IDs`, `Parent_keys`, `Type`, `Key`, `Count`, `Lock`, `Add`, `Add_sub`, `Modified`, `Menu`, `Menu_icon`, `Status`)
VALUES
  -- Root category: Solar Installation
  (2000, 1, 'solar-installation', 0, '2000', 0, '', '', 'listings', 'solar_installation', 0, '0', '0', '0', NOW(), '1', '', 'active'),

  -- Subcategories (Level 1)
  (2001, 1, 'solar-installation/residential-solar',     1, '2000.2001', 2000, '2000', 'solar_installation', 'listings', 'residential_solar',     0, '0', '0', '0', NOW(), '0', '', 'active'),
  (2002, 2, 'solar-installation/commercial-solar',      1, '2000.2002', 2000, '2000', 'solar_installation', 'listings', 'commercial_solar',      0, '0', '0', '0', NOW(), '0', '', 'active'),
  (2003, 3, 'solar-installation/solar-maintenance',     1, '2000.2003', 2000, '2000', 'solar_installation', 'listings', 'solar_maintenance',     0, '0', '0', '0', NOW(), '0', '', 'active'),
  (2004, 4, 'solar-installation/solar-battery-storage', 1, '2000.2004', 2000, '2000', 'solar_installation', 'listings', 'solar_battery_storage', 0, '0', '0', '0', NOW(), '0', '', 'active'),
  (2005, 5, 'solar-installation/solar-pool-heating',    1, '2000.2005', 2000, '2000', 'solar_installation', 'listings', 'solar_pool_heating',    0, '0', '0', '0', NOW(), '0', '', 'active'),
  (2006, 6, 'solar-installation/ev-charger-solar',      1, '2000.2006', 2000, '2000', 'solar_installation', 'listings', 'ev_charger_solar',      0, '0', '0', '0', NOW(), '0', '', 'active');


-- ============================================================================
-- SECTION 3: Insert solar listing fields
-- ============================================================================
-- Using IDs starting at 300
-- Columns: (ID, Key, Type, Default, Values, Condition, Multilingual, Details_page,
--           Add_page, Required, Map, Opt1, Opt2, Autocomplete, Status, Readonly, Contact)

INSERT INTO `{db_prefix}listing_fields`
  (`ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Multilingual`, `Details_page`, `Add_page`, `Required`, `Map`, `Opt1`, `Opt2`, `Autocomplete`, `Status`, `Readonly`, `Contact`)
VALUES
  -- 300: Company Name (title field for listings)
  (300, 'title',                'text',     '1', '100',  '',        '0', '0', '1', '1', '0', '0', '0', '0', 'active', '0', '0'),

  -- 301: About the Company
  (301, 'company_description',  'textarea', '',  '3000', 'html',    '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 302: Phone Number (Contact='1')
  (302, 'company_phone',        'phone',    '4', '7',    '1',       '0', '1', '1', '1', '0', '1', '',  '0', 'active', '0', '1'),

  -- 303: Email Address
  (303, 'company_email',        'text',     '',  '100',  'isEmail', '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 304: Website
  (304, 'company_website',      'text',     '',  '255',  'isUrl',   '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 305: Services Offered (checkbox, 6 options, translate values, 3 columns)
  (305, 'services_offered',     'checkbox', '',  '1,2,3,4,5,6',                 '', '0', '1', '1', '0', '0', '1', '3', '0', 'active', '0', '0'),

  -- 306: Solar Panel Brands (checkbox, 11 options, translate values, 3 columns)
  (306, 'panel_brands',         'checkbox', '',  '1,2,3,4,5,6,7,8,9,10,11',    '', '0', '1', '1', '0', '0', '1', '3', '0', 'active', '0', '0'),

  -- 307: Certifications (checkbox, 6 options, translate values, 3 columns)
  (307, 'certifications',       'checkbox', '',  '1,2,3,4,5,6',                 '', '0', '1', '1', '0', '0', '1', '3', '0', 'active', '0', '0'),

  -- 308: Financing Available (boolean)
  (308, 'financing_available',  'bool',     '0', '',     '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 309: Free Consultation (boolean)
  (309, 'free_consultation',    'bool',     '0', '',     '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 310: Warranty Years (number)
  (310, 'warranty_years',       'number',   '0', '2',    '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 311: System Size Range (select, 5 options)
  (311, 'system_size_range',    'select',   '',  '1,2,3,4,5',      '', '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 312: Years in Business (number)
  (312, 'years_in_business',    'number',   '0', '3',    '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 313: Installations Completed (number)
  (313, 'installations_completed', 'number', '0', '6',   '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 314: Service Area Radius (select, 5 options)
  (314, 'service_area_radius',  'select',   '',  '1,2,3,4,5',      '', '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 315: Google Rating (number, 1 digit, e.g. 4.5)
  (315, 'google_rating',        'number',   '0', '1',    '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0'),

  -- 316: Total Reviews (number)
  (316, 'total_reviews',        'number',   '0', '5',    '',        '0', '1', '1', '0', '0', '0', '0', '0', 'active', '0', '0');


-- ============================================================================
-- SECTION 4: Insert listing field groups
-- ============================================================================
-- Using IDs starting at 100
-- Columns: (ID, Key, Display, Columns, Header, Status)

INSERT INTO `{db_prefix}listing_groups`
  (`ID`, `Key`, `Display`, `Columns`, `Header`, `Status`)
VALUES
  (100, 'general_info',           '1', '1', '1', 'active'),
  (101, 'services_specialties',   '1', '0', '1', 'active'),
  (102, 'qualifications',         '1', '0', '1', 'active'),
  (103, 'pricing_details',        '1', '1', '1', 'active');


-- ============================================================================
-- SECTION 5: Insert listing relations (field-to-category mappings)
-- ============================================================================
-- Columns: (Position, Category_ID, Group_ID, Fields)
-- Map fields to root category 2000, then duplicate for each subcategory

INSERT INTO `{db_prefix}listing_relations`
  (`Position`, `Category_ID`, `Group_ID`, `Fields`)
VALUES
  -- Root category: Solar Installation (2000)
  (1, 2000, 100, '300,302,303,304'),
  (2, 2000, 101, '305,306,301'),
  (3, 2000, 102, '307,312,313,308,309'),
  (4, 2000, 103, '311,310,315,316,314'),
  (5, 2000, 21,  '37,191,192'),

  -- Residential Solar (2001)
  (1, 2001, 100, '300,302,303,304'),
  (2, 2001, 101, '305,306,301'),
  (3, 2001, 102, '307,312,313,308,309'),
  (4, 2001, 103, '311,310,315,316,314'),
  (5, 2001, 21,  '37,191,192'),

  -- Commercial Solar (2002)
  (1, 2002, 100, '300,302,303,304'),
  (2, 2002, 101, '305,306,301'),
  (3, 2002, 102, '307,312,313,308,309'),
  (4, 2002, 103, '311,310,315,316,314'),
  (5, 2002, 21,  '37,191,192'),

  -- Solar Maintenance & Repair (2003)
  (1, 2003, 100, '300,302,303,304'),
  (2, 2003, 101, '305,306,301'),
  (3, 2003, 102, '307,312,313,308,309'),
  (4, 2003, 103, '311,310,315,316,314'),
  (5, 2003, 21,  '37,191,192'),

  -- Solar Battery Storage (2004)
  (1, 2004, 100, '300,302,303,304'),
  (2, 2004, 101, '305,306,301'),
  (3, 2004, 102, '307,312,313,308,309'),
  (4, 2004, 103, '311,310,315,316,314'),
  (5, 2004, 21,  '37,191,192'),

  -- Solar Pool & Water Heating (2005)
  (1, 2005, 100, '300,302,303,304'),
  (2, 2005, 101, '305,306,301'),
  (3, 2005, 102, '307,312,313,308,309'),
  (4, 2005, 103, '311,310,315,316,314'),
  (5, 2005, 21,  '37,191,192'),

  -- EV Charger + Solar (2006)
  (1, 2006, 100, '300,302,303,304'),
  (2, 2006, 101, '305,306,301'),
  (3, 2006, 102, '307,312,313,308,309'),
  (4, 2006, 103, '311,310,315,316,314'),
  (5, 2006, 21,  '37,191,192');


-- ============================================================================
-- SECTION 6: Insert listing titles
-- ============================================================================
-- Map title field 300 to each category
-- Columns: (Position, Category_ID, Field_ID)

INSERT INTO `{db_prefix}listing_titles`
  (`Position`, `Category_ID`, `Field_ID`)
VALUES
  (1, 2000, 300),
  (1, 2001, 300),
  (1, 2002, 300),
  (1, 2003, 300),
  (1, 2004, 300),
  (1, 2005, 300),
  (1, 2006, 300);


-- ============================================================================
-- SECTION 7: Update listing plans (solar-specific plans)
-- ============================================================================
-- Remove existing default plans
DELETE FROM `{db_prefix}listing_plans`;

-- Insert solar-specific listing plans
-- Columns: (ID, Key, Position, Type, Allow_for, Sticky, Category_ID, Subcategories,
--           Featured, Advanced_mode, Standard_listings, Featured_listings, Color,
--           Limit, Price, Listing_period, Plan_period, Image, Image_unlim, Video,
--           Video_unlim, Listing_number, Cross, Status)

INSERT INTO `{db_prefix}listing_plans`
  (`ID`, `Key`, `Position`, `Type`, `Allow_for`, `Sticky`, `Category_ID`, `Subcategories`, `Featured`, `Advanced_mode`, `Standard_listings`, `Featured_listings`, `Color`, `Limit`, `Price`, `Listing_period`, `Plan_period`, `Image`, `Image_unlim`, `Video`, `Video_unlim`, `Listing_number`, `Cross`, `Status`)
VALUES
  -- Free Listing: basic directory listing
  (50, 'free_listing',    1, 'listing', '', '1', '', '1', '0', '0', 0, 0, '',       0, 0,  90,  0, 3,  '0', 0, '0', 0, 0, 'active'),

  -- Pro Listing: enhanced visibility, featured badge
  (51, 'pro_listing',     2, 'listing', '', '1', '', '1', '1', '0', 0, 0, '3b82f6', 0, 29, 365, 0, 10, '0', 1, '0', 0, 0, 'active'),

  -- Premium Listing: maximum exposure, unlimited photos
  (52, 'premium_listing', 3, 'listing', '', '1', '', '1', '1', '0', 0, 0, 'f59e0b', 0, 79, 365, 0, 0,  '1', 3, '0', 0, 0, 'active');


-- ============================================================================
-- SECTION 8: Update listing types
-- ============================================================================
-- Update the 'listings' type to point to solar root category
UPDATE `{db_prefix}listing_types`
SET `Cat_general_cat`  = 2000,
    `Cat_general_only` = '1',
    `Search_home`      = '1',
    `Arrange_field`    = '',
    `Arrange_values`   = '',
    `Menu_icon`        = 'sun.svg'
WHERE `Key` = 'listings';

-- Trash all other listing types (motors, jobs, property, services)
UPDATE `{db_prefix}listing_types`
SET `Status` = 'trash'
WHERE `Key` != 'listings';

-- Trash pages for disabled listing types
UPDATE `{db_prefix}pages`
SET `Status` = 'trash'
WHERE `Key` IN (
  'lt_motors', 'my_motors',
  'lt_jobs', 'my_jobs',
  'lt_property', 'my_property',
  'lt_services', 'my_services'
);

-- Update the listings page path for solar branding
UPDATE `{db_prefix}pages`
SET `Path` = 'solar-installers'
WHERE `Key` = 'lt_listings';

-- Update the dealer/agent page for solar branding
UPDATE `{db_prefix}pages`
SET `Path` = 'solar-companies'
WHERE `Key` = 'at_dealer';


-- ============================================================================
-- SECTION 9: Update search forms
-- ============================================================================
-- Delete non-listings search forms (already done in Section 1 cleanup)
-- The remaining forms are: listings_quick(15), listings_advanced(16),
--                          listings_myads(26), listings_on_map(41)

-- Insert search form relations for listings_quick (ID=15)
-- Columns: (Position, Category_ID, Group_ID, Fields)
-- Note: Category_ID here refers to the search form ID, Fields is a single Field_ID
INSERT INTO `{db_prefix}search_forms_relations`
  (`Position`, `Category_ID`, `Group_ID`, `Fields`)
VALUES
  -- Quick search: category selector, location, services
  (1, 15, 0, '88'),
  (2, 15, 0, '37'),
  (3, 15, 0, '305'),

  -- Advanced search: category, location levels, services, certifications, financing, size, rating
  (1, 16, 100, '88,305,307,308,311,315'),
  (2, 16, 21,  '37,191'),

  -- My ads search: category, status, plan, active till
  (1, 26, 0, '88'),
  (2, 26, 0, '-3'),
  (3, 26, 0, '-1'),
  (4, 26, 0, '-2'),

  -- On map search: category, services
  (1, 41, 0, '88'),
  (2, 41, 0, '305');


-- ============================================================================
-- SECTION 10: Insert ALL language keys (English)
-- ============================================================================
-- Columns: (Code, Module, JS, Key, Value, Target_key, Modified, Plugin, Status)

-- --------------------------------------------------------------------------
-- 10a. Category names (Module = 'category')
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'category', '0', 'categories+name+solar_installation',     'Solar Installation',            '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+residential_solar',      'Residential Solar',             '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+commercial_solar',       'Commercial Solar',              '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+solar_maintenance',      'Solar Maintenance & Repair',    '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+solar_battery_storage',  'Solar Battery Storage',         '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+solar_pool_heating',     'Solar Pool & Water Heating',    '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+name+ev_charger_solar',       'EV Charger + Solar',            '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10b. Category descriptions (Module = 'category')
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'category', '0', 'categories+des+solar_installation',     'Find trusted solar installation companies near you',                             '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+residential_solar',      'Solar panel installation for homes and residential properties',                   '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+commercial_solar',       'Solar energy solutions for businesses, warehouses, and commercial buildings',     '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+solar_maintenance',      'Solar panel cleaning, repair, and ongoing maintenance services',                  '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+solar_battery_storage',  'Home and commercial battery storage and backup power solutions',                  '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+solar_pool_heating',     'Solar-powered pool heating and water heating systems',                            '', '0', '', 'active'),
  ('en', 'category', '0', 'categories+des+ev_charger_solar',       'Electric vehicle charger installation integrated with solar energy',              '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10c. Field names (Module = 'common')
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+title',                    'Company Name',              '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+company_description',      'About the Company',         '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+company_phone',            'Phone Number',              '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+company_email',            'Email Address',             '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+company_website',          'Website',                   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered',         'Services Offered',          '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands',             'Solar Panel Brands',        '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications',           'Certifications',            '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+financing_available',      'Financing Available',       '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+free_consultation',        'Free Consultation',         '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+warranty_years',           'Warranty (Years)',           '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+system_size_range',        'System Size Range',         '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+years_in_business',        'Years in Business',         '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+installations_completed',  'Installations Completed',   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+service_area_radius',      'Service Area',              '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+google_rating',            'Google Rating',             '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+total_reviews',            'Total Reviews',             '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10d. Field option values: services_offered (checkbox, field key = services_offered)
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+services_offered_1', 'Residential Solar',          '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered_2', 'Commercial Solar',           '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered_3', 'Battery Storage',            '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered_4', 'EV Charger Installation',    '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered_5', 'Maintenance & Repair',       '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+services_offered_6', 'Pool/Water Heating',         '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10e. Field option values: panel_brands (checkbox, field key = panel_brands)
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+panel_brands_1',  'SunPower',       '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_2',  'Tesla',          '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_3',  'LG',             '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_4',  'Panasonic',      '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_5',  'Canadian Solar', '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_6',  'Enphase',        '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_7',  'SolarEdge',      '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_8',  'Generac',        '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_9',  'REC',            '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_10', 'Trina Solar',    '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+panel_brands_11', 'JinkoSolar',     '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10f. Field option values: certifications (checkbox, field key = certifications)
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+certifications_1', 'NABCEP Certified',           '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications_2', 'SEIA Member',                '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications_3', 'Licensed Contractor',        '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications_4', 'BBB Accredited',             '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications_5', 'Tesla Certified Installer',  '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+certifications_6', 'Enphase Certified',          '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10g. Field option values: system_size_range (select, field key = system_size_range)
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+system_size_range_1', 'Under 5 kW',   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+system_size_range_2', '5 - 10 kW',    '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+system_size_range_3', '10 - 25 kW',   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+system_size_range_4', '25 - 100 kW',  '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+system_size_range_5', '100+ kW',      '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10h. Field option values: service_area_radius (select, field key = service_area_radius)
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_fields+name+service_area_radius_1', '25 miles',   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+service_area_radius_2', '50 miles',   '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+service_area_radius_3', '100 miles',  '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+service_area_radius_4', 'Statewide',  '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_fields+name+service_area_radius_5', 'Nationwide', '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10i. Listing group names (Module = 'common')
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_groups+name+general_info',         'General Information',            '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_groups+name+services_specialties', 'Services & Specialties',        '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_groups+name+qualifications',       'Qualifications & Experience',    '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_groups+name+pricing_details',      'System Details',                 '', '0', '', 'active');

-- --------------------------------------------------------------------------
-- 10j. Listing plan names and descriptions (Module = 'common')
-- --------------------------------------------------------------------------
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
VALUES
  ('en', 'common', '0', 'listing_plans+name+free_listing',    'Free Listing',              '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_plans+des+free_listing',     'Basic directory listing with essential information',                      '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_plans+name+pro_listing',     'Pro Listing',               '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_plans+des+pro_listing',      'Enhanced visibility with featured badge and more photos',                 '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_plans+name+premium_listing', 'Premium Listing',           '', '0', '', 'active'),
  ('en', 'common', '0', 'listing_plans+des+premium_listing',  'Maximum exposure with top placement and unlimited photos',               '', '0', '', 'active');


-- ============================================================================
-- SECTION 11: Update site config
-- ============================================================================
-- The site name in Flynax is stored via the lang key 'advanced_site_name'
-- referenced by {advanced_site_name} placeholders throughout the system
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'SolarListings'
WHERE `Key` = 'advanced_site_name' AND `Code` = 'en';

-- Also update the email site name
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'SolarListings'
WHERE `Key` = 'email_site_name' AND `Code` = 'en';

-- Update the copy rights text
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'SolarListings'
WHERE `Key` = 'copy_rights' AND `Code` = 'en';


-- ============================================================================
-- SECTION 12: Update lang keys for site branding
-- ============================================================================

-- Home page name in navigation
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'SolarListings - Find Solar Installers Near You'
WHERE `Key` = 'pages+name+home' AND `Code` = 'en';

-- Home page meta title
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Find the best solar installation companies in your area. Compare ratings, certifications, and services.'
WHERE `Key` = 'pages+title+home' AND `Code` = 'en';

-- Update listings type page name
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Solar Installers'
WHERE `Key` = 'pages+name+lt_listings' AND `Code` = 'en';

-- Update "my listings" page name
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'My Listings'
WHERE `Key` = 'pages+name+my_listings' AND `Code` = 'en';

-- Update listings type name
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Solar Installers'
WHERE `Key` = 'listing_types+name+listings' AND `Code` = 'en';

-- Update the "Add listing" page
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Add Your Solar Company'
WHERE `Key` = 'pages+name+add_listing' AND `Code` = 'en';


-- ============================================================================
-- SECTION 13: Update account type labels
-- ============================================================================

-- Rename 'dealer' account type to 'Solar Company'
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Solar Company'
WHERE `Key` = 'account_types+name+dealer' AND `Code` = 'en';

-- Add description for dealer account type
-- First try to update existing, if none exists we insert
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Register as a solar installation company'
WHERE `Key` = 'account_types+des+dealer' AND `Code` = 'en';

-- Insert if the des key does not exist yet
INSERT INTO `{db_prefix}lang_keys`
  (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`)
SELECT 'en', 'common', '0', 'account_types+des+dealer', 'Register as a solar installation company', '', '0', '', 'active'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `{db_prefix}lang_keys`
  WHERE `Key` = 'account_types+des+dealer' AND `Code` = 'en'
);

-- Update the dealer page name
UPDATE `{db_prefix}lang_keys`
SET `Value` = 'Solar Companies'
WHERE `Key` = 'pages+name+at_dealer' AND `Code` = 'en';


-- ============================================================================
-- DONE: Solar Installation Directory setup is complete
-- ============================================================================
-- Summary of changes:
--   - Removed all default categories and replaced with 7 solar categories
--   - Added 17 solar-specific listing fields (company info, services, certs, etc.)
--   - Created 4 custom field groups + reused location group
--   - Mapped all fields to all solar categories
--   - Created 3 solar-specific listing plans (Free, Pro, Premium)
--   - Configured listings type to use solar root category
--   - Disabled motors, jobs, property, and services listing types
--   - Updated search forms for solar-relevant fields
--   - Added all English language keys for categories, fields, options, groups, plans
--   - Updated site branding to SolarListings
--   - Renamed dealer account type to Solar Company
-- ============================================================================
