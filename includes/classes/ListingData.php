<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: LISTINGDATA.PHP
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

namespace Flynax\Classes;

use Flynax\Utils\Valid;

/**
 * Listings Data class
 *
 * @since 4.10.0
 */
class ListingData
{
    /**
     * Adds options to the listing
     *
     * @param array $options   Options to add
     * @param int   $listingID Listing ID
     * @return bool
     */
    public static function addOptions(array $options, int $listingID): bool
    {
        if (!$options || !$listingID) {
            return false;
        }

        $insertOptions = [];
        foreach ($options as $fieldKey => $optionData) {
            $insertOptions[] = [
                'Listing_ID' => $listingID,
                'Key'        => $fieldKey,
                'Value'      => $optionData,
            ];
        }
        $GLOBALS['rlDb']->insert($insertOptions, 'listings_data', ['Value']);

        return true;
    }

    /**
     * Delete options by Listing ID or option keys
     *
     * @param  int|null $listingID  ID of the listing
     * @param  array    $optionKeys Array of option keys
     * @return bool
     */
    public static function deleteOptions(?int $listingID = null, array $optionKeys = []): bool
    {
        global $rlDb;

        if (!$listingID && !$optionKeys) {
            return false;
        }

        if ($listingID && $optionKeys) {
            $rlDb->query(
                "DELETE FROM `{db_prefix}listings_data`
                 WHERE `Listing_ID` = {$listingID}
                 AND `Key` IN ('" . implode("', '", $optionKeys) . "')"
            );
        } elseif ($listingID && !$optionKeys) {
            $rlDb->delete(['Listing_ID' => $listingID], 'listings_data', null, 0);
        } elseif (!$listingID && $optionKeys) {
            $rlDb->query(
                "DELETE FROM `{db_prefix}listings_data`
                 WHERE `Key` IN ('" . implode("', '", $optionKeys) . "')"
            );
        }

        return true;
    }

    /**
     * Updates options of the listing
     *
     * @param array $options   Options to add
     * @param int   $listingID Listing ID
     * @return bool
     */
    public static function updateOptions(array $options, int $listingID): bool
    {
        global $rlDb;

        if (!$options || !$listingID) {
            return false;
        }

        $existsOptions = self::getOptions($listingID);
        $insertOptions = [];
        $updateOptions = [];

        foreach ($options as $fieldKey => $optionData) {
            if (isset($existsOptions[$fieldKey])) {
                if (Valid::isJson($optionData)) {
                    $optionValue = json_decode($optionData, true);
                    $optionValue = is_array($optionValue) ? array_filter($optionValue) : [];
                } else {
                    $optionValue = $optionData;
                }

                if (!empty($optionValue)) {
                    $updateOptions[] = [
                        'fields' => [
                            'Value' => $optionData,
                        ],
                        'where' => [
                            'Listing_ID' => $listingID,
                            'Key'        => $fieldKey,
                        ]
                    ];
                } else {
                    self::deleteOptions($listingID, [$fieldKey]);
                }
            } else {
                $insertOptions[] = [
                    'Listing_ID' => $listingID,
                    'Key'        => $fieldKey,
                    'Value'      => $optionData,
                ];
            }
        }

        $rlDb->insert($insertOptions, 'listings_data', ['Value']);
        $rlDb->update($updateOptions, 'listings_data', ['Value']);

        return true;
    }

    /**
     * Retrieve options for the listing
     *
     * @param  int|string $id Listing ID
     * @return array          Options values as array
     */
    public static function getOptions($id): array
    {
        if (!$id) {
            return [];
        }

        static $options;

        if ($options[$id] === null) {
            $GLOBALS['rlDb']->outputRowsMap = ['Key', 'Value'];
            $options[$id] = (array) $GLOBALS['rlDb']->fetch(
                ['Key', 'Value'],
                ['Listing_ID' => $id],
                null, null, 'listings_data'
            );

            $options[$id] = array_map(function ($option) {
                return $option && Valid::isJson($option) ? json_decode($option, true) : $option;
            }, $options[$id]);
        }

        return $options[$id];
    }

    /**
     * Fill options for listings
     *
     * @param array        $listings      Listings array
     * @param array|string $type          Listing type details (key or array)
     * @param bool         $addData       Add listing title, url and form with fields
     * @param bool         $addAllOptions Add all options
     * @param string       $formKey       Form key
     *
     * @return array Listings array with filled options
     */
    public static function fillOptions(
        array $listings,
        $type = null,
        bool $addData = true,
        bool $addAllOptions = false,
        string $formKey = 'short_forms'
    ): array
    {
        global $rlListings, $reefless, $rlDb, $rlHook, $rlListingTypes;

        if (!$listings) {
            return [];
        }

        $formFields = null;
        $generalCarFormFields = null;
        $type = is_string($type) && $type ? $rlListingTypes->types[$type] : $type;

        if (is_array($type) && !empty($type['Cat_general_only'])) {
            $generalCarFormFields = $rlListings->getFormFields($type['Cat_general_cat'], $formKey, $type['Key']);
        }

        $listingIDs = array_map(function ($listing) {
            return $listing['ID'];
        }, $listings);
        $listingIDs = implode(',', $listingIDs);

        $listingsData = $rlDb->fetch('*', null, "WHERE `Listing_ID` IN ({$listingIDs})", null, 'listings_data');

        $options = [];
        foreach ($listingsData as $optionData) {
            $optionValue = $optionData['Value'] && Valid::isJson($optionData['Value'])
            ? json_decode($optionData['Value'], true)
            : $optionData['Value'];

            $options[$optionData['Listing_ID']][$optionData['Key']] = $optionValue;
        }
        unset($listingsData, $optionValue, $optionData);

        $optionKeys = [];
        $rlHook->load('phpListingDataFillOptionsBefore', $optionKeys, $listings, $options);

        foreach ($listings as &$listing) {
            if ($addData) {
                if ($generalCarFormFields) {
                    $formFields = $generalCarFormFields;
                } else {
                    $formFields = $rlListings->getFormFields(
                        $listing['Category_ID'],
                        $formKey,
                        $listing['Listing_type'],
                        $listing['Parent_IDs']
                    );
                }

                $listingFields = $formFields;

                foreach ($listingFields as $fieldIndex => &$field) {
                    if (empty($field)
                        || (empty($listing[$field['Key']])
                            && empty($options[$listing['ID']][$field['Key']])
                            && !in_array($field['Type'], ['bool', 'price'])
                        )
                    ) {
                        unset($listingFields[$fieldIndex]);
                        continue;
                    }

                    self::fillOption($field, $listing, $options[$listing['ID']], $formKey);
                }

                $listing['fields'] = $listingFields;

                unset($field, $listingFields);

                $listing['listing_title'] = $rlListings->getListingTitle(
                    $listing['Category_ID'],
                    $listing,
                    $listing['Listing_type'],
                    false,
                    $listing['Parent_IDs']
                );

                $listing['url'] = $reefless->getListingUrl($listing);
                $listing['listing_link'] = $listing['url'];
            }

            if ($addAllOptions && $options[$listing['ID']]) {
                $optionKeys = array_keys($options[$listing['ID']]);
            }

            foreach ($optionKeys as $optionKey) {
                if (isset($options[$listing['ID']][$optionKey]) && !isset($listing[$optionKey])) {
                    $listing[$optionKey] = $options[$listing['ID']][$optionKey];
                }
            }
        }

        $rlHook->load('phpListingDataFillOptionsAfter', $listings, $options, $type);

        return $listings;
    }

    /**
     * Fill options for one listing
     *
     * @param array|null   $listing       Listing array
     * @param array|string $type          Listing type details (key or array)
     * @param bool         $addData       Add listing title, url and form with fields
     * @param bool         $addAllOptions Add all options
     * @param string       $formKey       Form key
     *
     * @return array Listing array with filled options
     */
    public static function fillOptionsForListing(
        ?array $listing,
        $type = null,
        bool $addData = true,
        bool $addAllOptions = false,
        string $formKey = 'short_forms'
    ): array
    {
        if (!$listing) {
            return [];
        }

        return reset(self::fillOptions([$listing], $type, $addData, $addAllOptions, $formKey));
    }

    /**
     * Fill options for one listing field
     *
     * @param array|null &$field     Field array
     * @param array      $listing    Listing array
     * @param array|null $options    Options array
     * @param string     $formKey    Short forms key
     * @param bool       $tags       Use html tags
     * @param bool       $stripTags  Strip tags for html fields
     * @param ?string    $customLang Use custom language code
     */
    public static function fillOption(
        ?array &$field = null,
        array $listing = [],
        ?array $options = null,
        string $formKey = 'short_forms',
        bool $tags = true,
        bool $stripTags = false,
        ?string $customLang = null
    ) {
        global $rlCommon, $rlLang;

        /**
         * Flag indicating that the listing already has filled options
         *
         * @todo Remove this flag here and in the $rlCommon->adaptValue()
         *       when all plugins will use the ListingData::fillOptions() method.
         */
        $field['OptionsFilled'] = true;

        if ($formKey === 'listing_form') {
            $field['source'] = is_string($listing[$field['Key']])
            ? explode(',', $listing[$field['Key']])
            : [];
        }

        $field['value'] = $rlCommon->adaptValue(
            $field,
            $listing[$field['Key']],
            'listing',
            $listing['ID'],
            $tags,
            $stripTags,
            false,
            $customLang,
            $listing['Account_ID'],
            $formKey,
            $listing['Listing_type']
        );

        unset($field['OptionsFilled']);

        $listingOptions = $options[$listing['ID']] ? $options[$listing['ID']][$field['Key']] : $options[$field['Key']];

        if (isset($listingOptions)) {
            switch ($field['Type']) {
                case 'price':
                    if ($listingOptions['option']) {
                        $field['value'] = $rlLang->getPhrase("data_formats+name+{$listingOptions['option']}");
                    }

                    $field['Options'] = $listingOptions;
                    break;

                default:
                    if (is_array($listingOptions)) {
                        $field['Options'] = $listingOptions;
                    } else {
                        $field['value'] = $listingOptions;
                    }
                    break;
            }
        }
    }
}
