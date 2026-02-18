<?php

/******************************************************************************
 *
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLCUSTOM.CLASS.PHP
 *
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *
 *  https://www.flynax.com
 ******************************************************************************/

/**
 * Class rlCustom
 * SolarListings custom hooks for Schema.org structured data, SEO, and branding.
 *
 * @since 4.10.1
 */
class rlCustom
{
    public function __construct()
    {
        // Inject solar CSS on all pages
        $GLOBALS['rlHook']->addCustomHook('tplHeader', [$this, 'hookInjectSolarAssets']);

        // Add Schema.org structured data on listing detail pages
        $GLOBALS['rlHook']->addCustomHook('listingDetailsBottomTpl', [$this, 'hookSchemaOrg']);

        // Add SEO meta tags for listing pages
        $GLOBALS['rlHook']->addCustomHook('listingDetailsTopTpl', [$this, 'hookSeoMeta']);

        // Pipeline health monitoring (runs via Flynax cron every 30 min)
        $GLOBALS['rlHook']->addCustomHook('cronAdditional', [$this, 'hookPipelineMonitor']);
    }

    /**
     * Inject solar CSS stylesheet on all pages
     */
    public function hookInjectSolarAssets()
    {
        $baseUrl = RL_URL_HOME;
        echo '<link rel="stylesheet" type="text/css" href="' . $baseUrl . 'custom/templates/general_flatty/css/solar.css" />';
    }

    /**
     * Add Schema.org LocalBusiness structured data on listing detail pages
     */
    public function hookSchemaOrg()
    {
        global $listing_data, $listing, $photos, $config;

        if (empty($listing_data) || empty($listing_data['ID'])) {
            return;
        }

        // Collect field values from the listing groups
        $fields = [];
        if (is_array($listing)) {
            foreach ($listing as $group) {
                if (!empty($group['Fields'])) {
                    foreach ($group['Fields'] as $field) {
                        if (!empty($field['Key'])) {
                            $fields[$field['Key']] = $field['value'] ?? '';
                        }
                    }
                }
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => ['LocalBusiness', 'HomeAndConstructionBusiness'],
            'name' => $listing_data['listing_title'] ?? ($fields['title'] ?? ''),
            'description' => $fields['company_description'] ?? '',
        ];

        // Address
        $address = [];
        if (!empty($fields['address'])) {
            $address['streetAddress'] = $fields['address'];
        }
        if (!empty($listing_data['country_level2'])) {
            $address['addressLocality'] = $listing_data['country_level2'];
        }
        if (!empty($listing_data['country_level1'])) {
            $address['addressRegion'] = $listing_data['country_level1'];
        }
        if (!empty($fields['zip'])) {
            $address['postalCode'] = $fields['zip'];
        }
        $address['addressCountry'] = 'US';
        if (!empty($address)) {
            $schema['address'] = array_merge(['@type' => 'PostalAddress'], $address);
        }

        // Geo coordinates
        if (!empty($listing_data['Loc_latitude']) && !empty($listing_data['Loc_longitude'])) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float)$listing_data['Loc_latitude'],
                'longitude' => (float)$listing_data['Loc_longitude'],
            ];
        }

        // Phone
        if (!empty($fields['company_phone'])) {
            $schema['telephone'] = $fields['company_phone'];
        }

        // Website
        if (!empty($fields['company_website'])) {
            $schema['url'] = $fields['company_website'];
        }

        // Rating
        if (!empty($fields['google_rating']) && (float)$fields['google_rating'] > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float)$fields['google_rating'],
                'bestRating' => 5,
                'worstRating' => 1,
            ];
            if (!empty($fields['total_reviews'])) {
                $schema['aggregateRating']['reviewCount'] = (int)$fields['total_reviews'];
            }
        }

        // Image
        if (!empty($listing_data['Main_photo'])) {
            $schema['image'] = RL_URL_HOME . 'files/' . $listing_data['Main_photo'];
        }

        // Services
        if (!empty($fields['services_offered'])) {
            $schema['makesOffer'] = [];
            $services = array_map('trim', explode(',', $fields['services_offered']));
            foreach ($services as $service) {
                if ($service) {
                    $schema['makesOffer'][] = [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => $service,
                        ],
                    ];
                }
            }
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * Enhance SEO meta tags for listing detail pages
     */
    public function hookSeoMeta()
    {
        global $listing_data, $listing, $rlSmarty;

        if (empty($listing_data) || empty($listing_data['ID'])) {
            return;
        }

        // Collect field values
        $fields = [];
        if (is_array($listing)) {
            foreach ($listing as $group) {
                if (!empty($group['Fields'])) {
                    foreach ($group['Fields'] as $field) {
                        if (!empty($field['Key'])) {
                            $fields[$field['Key']] = $field['value'] ?? '';
                        }
                    }
                }
            }
        }

        $companyName = $listing_data['listing_title'] ?? '';
        $city = $listing_data['country_level2'] ?? '';
        $state = $listing_data['country_level1'] ?? '';
        $rating = $fields['google_rating'] ?? '';
        $reviews = $fields['total_reviews'] ?? '';

        // Build meta description
        $metaParts = [];
        $metaParts[] = $companyName;
        if ($city && $state) {
            $metaParts[] = "solar installer in {$city}, {$state}";
        }
        if ($rating) {
            $metaParts[] = "rated {$rating}/5";
        }
        if ($reviews) {
            $metaParts[] = "({$reviews} reviews)";
        }
        if (!empty($fields['services_offered'])) {
            $metaParts[] = "Services: {$fields['services_offered']}";
        }

        $metaDescription = implode(' - ', $metaParts);
        $metaDescription = substr($metaDescription, 0, 160);

        // Inject meta tags
        echo '<meta name="description" content="' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '" />';

        // Open Graph tags for social sharing
        echo '<meta property="og:title" content="' . htmlspecialchars($companyName . ' | SolarListings', ENT_QUOTES, 'UTF-8') . '" />';
        echo '<meta property="og:description" content="' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '" />';
        echo '<meta property="og:type" content="business.business" />';

        if (!empty($listing_data['Main_photo'])) {
            echo '<meta property="og:image" content="' . RL_URL_HOME . 'files/' . htmlspecialchars($listing_data['Main_photo'], ENT_QUOTES, 'UTF-8') . '" />';
        }
    }

    /**
     * Pipeline health monitoring — detects stuck or stale pipeline runs.
     * Called by Flynax cron (cronAdditional hook) every 30 minutes.
     */
    public function hookPipelineMonitor()
    {
        global $rlDb, $config;

        $prefix = RL_DBPREFIX;

        // Check for stuck pipeline runs (running > 6 hours)
        $stuckSql = "SELECT `ID`, `run_type`, `region`, `started_at`
                     FROM `{$prefix}solar_pipeline_runs`
                     WHERE `status` = 'running'
                       AND `started_at` < DATE_SUB(NOW(), INTERVAL 6 HOUR)";
        $stuckResult = $rlDb->getAll($stuckSql);

        if (!empty($stuckResult)) {
            foreach ($stuckResult as $run) {
                $rlDb->query(
                    "UPDATE `{$prefix}solar_pipeline_runs`
                     SET `status` = 'failed',
                         `error_message` = 'Automatically marked failed: exceeded 6-hour timeout',
                         `completed_at` = NOW()
                     WHERE `ID` = {$run['ID']}"
                );
            }

            // Notify admin
            $adminEmail = $config['notifications_email'] ?? '';
            if ($adminEmail) {
                $count = count($stuckResult);
                @mail(
                    $adminEmail,
                    'SolarListings Pipeline Alert: Stuck Run Detected',
                    "{$count} pipeline run(s) were stuck for over 6 hours and have been marked as failed.\n"
                    . "Check the admin panel or scripts/logs/ for details.",
                    "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'findsolarinstallers.xyz')
                );
            }
        }

        // Check for stale regions (not scraped in 30+ days)
        $staleSql = "SELECT COUNT(*) as cnt FROM `{$prefix}solar_region_schedule`
                     WHERE `last_scraped_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)
                       AND `enabled` = 1";
        $staleResult = $rlDb->getRow($staleSql);

        if (!empty($staleResult) && (int)$staleResult['cnt'] > 10) {
            $adminEmail = $config['notifications_email'] ?? '';
            if ($adminEmail) {
                @mail(
                    $adminEmail,
                    'SolarListings Pipeline Alert: Stale Regions',
                    "{$staleResult['cnt']} regions have not been scraped in 30+ days.\n"
                    . "Check that the weekly cron job is running: pipeline_orchestrator.py --mode weekly",
                    "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'findsolarinstallers.xyz')
                );
            }
        }
    }
}
