<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLNEWS.CLASS.PHP
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

class rlNews
{
    /**
     * @var - Count of news
     */
    public $calc_news;

    /**
     * Get list of news
     *
     * @since 4.9.3 - Added $getPhrases, $useCache, $categoryID, $random parameters & added cache for news box
     *
     * @param  int   $id              - News id
     * @param  bool  $page            - Page mode
     * @param  int   $pg              - Start position
     * @param  bool  $getPhrases      - Add phrases to data or not
     * @param  bool  $useCache        - Get news from cache at first
     * @param  int   $categoryID      - Filter news by necessary category
     * @param  bool  $orderByCategory - News by necessary category will be first
     * @param  bool  $excludeID       - ID of article which must exclude from result
     * @return array                  - List of news
     */
    public function get(
        $id = false,
        $page = false,
        $pg = 1,
        $getPhrases = true,
        bool $useCache = true,
        int $categoryID = 0,
        bool $orderByCategory = false,
        int $excludeID = 0
    ): array {
        global $rlDb, $config, $rlLang, $rlCache;

        $news = [];
        if ($config['cache'] && !$page && $useCache) {
            $news = $rlCache->get('cache_news_in_box');
        }

        if (!$news) {
            $sql = "SELECT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Path` AS `Category_Path`";

            if ($categoryID && $orderByCategory) {
                $sql .= ", IF(`T1`.`Category_ID` = {$categoryID}, 1, 0) AS `Category_relevance`";
            }

            $sql .= " FROM `{db_prefix}news` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}news_categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Status` = 'active' ";

            if ($id) {
                $sql .= "AND `T1`.`ID` = '{$id}' ";
            }

            if ($categoryID && !$orderByCategory) {
                $sql .= "AND `T2`.`ID` = '{$categoryID}' ";
            }

            if ($excludeID) {
                $sql .= "AND `T1`.`ID` <> $excludeID ";
            }

            $GLOBALS['rlHook']->load('rlNewsGetSql', $sql); // from v4.1.0

            $sql .= 'ORDER BY ' . ($categoryID && $orderByCategory ? '`Category_relevance` DESC, ' : '') . '`T1`.`Date` DESC ';

            if (!$page) {
                $sql .= "LIMIT " . $config['news_block_news_in_block'];
            } else {
                $start = 0;
                if ($pg > 1) {
                    $start = ($pg - 1) * $config['news_at_page'];
                }

                $sql .= "LIMIT {$start}," . $config['news_at_page'];
            }

            if ($id) {
                $news = $rlDb->getRow($sql);
            } else {
                $news = $rlDb->getAll($sql);
            }

            $this->calc_news = $rlDb->getTotalCount($sql);
        }

        if ($getPhrases) {
            $news = $rlLang->replaceLangKeys($news, 'news', ['title', 'content', 'meta_description']);

            if ($id && $news['Category_ID']) {
                $news['Category_Name'] = $rlLang->getPhrase("news_categories+name+{$news['Category_ID']}");
            }
        }

        return (array) $news;
    }

    /**
     * Get list of news categories
     *
     * @since 4.9.3
     *
     * @param  int   $id         - Get info about necessary category by ID
     * @param  bool  $useCache   - Get categories from cache at first
     * @param  bool  $getPhrases - Add phrases to data or not
     * @return array
     */
    public function getCategories(int $id = 0, bool $useCache = true, $getPhrases = true): array
    {
        global $rlDb, $config, $rlCache, $rlHook, $rlLang, $lang;

        $id = (int) $id;

        $categories = [];
        if ($config['cache'] && !$id && $useCache) {
            $categories = (array) $rlCache->get('cache_news_categories');
        }

        if (!$categories) {
            $sql = "SELECT * FROM `{db_prefix}news_categories` ";
            $sql .= "WHERE `Status` = 'active' ";

            if ($id) {
                $sql .= "AND `ID` = {$id} ";
            }

            $rlHook->load('phpNewsCategoriesSqlWhere', $sql);

            if ($id) {
                $categories = $rlDb->getRow($sql);
            } else {
                $categories = $rlDb->getAll($sql);
            }
        }

        if ($getPhrases) {
            if ($id) {
                $categories['Name']             = $lang["news_categories+name+{$categories['ID']}"];
                $categories['Title']            = $lang["news_categories+title+{$categories['ID']}"] ?: $categories['Name'];
                $categories['H1']               = $lang["news_categories+h1+{$categories['ID']}"];
                $categories['Meta_description'] = $lang["news_categories+meta_description+{$categories['ID']}"];
            } else {
                foreach ($categories as &$category) {
                    $category['Name'] = $rlLang->getPhrase("news_categories+name+{$category['ID']}");
                }
            }
        }

        return (array) $categories;
    }

    /**
     * Get more random news from category
     *
     * @since 4.9.3
     *
     * @param  int   $categoryID
     * @param  int   $articleID  - ID of article which must exclude from result
     * @return array
     */
    public function getMoreNews(int $categoryID, int $articleID = 0): array
    {
        return $this->get(0, false, 1, true, false, $categoryID, true, $articleID);
    }
}
