<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: NEWS.INC.PHP
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

// Register CSS
$rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/news/news.css', $page_info['Controller']);

$path = $rlValid->xSql($config['mod_rewrite'] ? ($_GET['nvar_2'] ?: $_GET['nvar_1']) : $_GET['path']);
// trailing number mode
if ($_GET['listing_id'] && $config['mod_rewrite']) {
    $path .= '-' . $_GET['listing_id'];
}
$article_id = $rlDb->getOne('ID', "`Path` = '{$path}'", 'news');

$page_info['current'] = (int) $_GET['pg'];

$reefless->loadClass('News');

if ($article_id) {
    $article = $rlNews->get($article_id, true);
    $rlSmarty->assign_by_ref('article', $article);

    $newsCategoryID = (int) $article['Category_ID'];

    // Get random another news from same category
    $otherArticles = $rlNews->getMoreNews($newsCategoryID, (int) $article['ID']);
    $rlSmarty->assign_by_ref('otherArticles', $otherArticles);

    $page_info['meta_description'] = $article['meta_description'];
    $page_info['title']            = $article['title'];
    $page_info['h1']               = $article['title'];

    if ($newsCategoryID) {
        $newsCategoryName = $lang["news_categories+name+{$newsCategoryID}"];
        $newsCategoryPath = $rlDb->getOne('Path', "`ID` = {$newsCategoryID}", 'news_categories');

        $bread_crumbs[] = [
            'title' => $newsCategoryName,
            'name'  => $newsCategoryName,
            'url'   => $reefless->getPageUrl('news', ['category' => $newsCategoryPath]),
        ];
    }

    $bread_crumbs[] = ['title' => $page_info['title']];

    // Build link to return to news list
    $additionalPath = null;
    $additionalVars = null;
    if ($_SESSION['news_last_viewed_page'] >= 2) {
        if ($config['mod_rewrite']) {
            $additionalPath = ["index{$_SESSION['news_last_viewed_page']}"];
        } else {
            $additionalVars = "pg={$_SESSION['news_last_viewed_page']}";
        }
    }

    $back_link = $reefless->getPageUrl('news', $additionalPath, null, $additionalVars);
    $rlSmarty->assign_by_ref('back_link', $back_link);

    $viewedNews = $_COOKIE['viewedNews'] ? json_decode($_COOKIE['viewedNews'], true) : [];

    if (!in_array($article_id, $viewedNews)) {
        $rlDb->query("UPDATE `{db_prefix}news` SET `Views` = `Views` + 1 WHERE `ID` = {$article_id} LIMIT 1");
        $viewedNews[] = $article_id;
        $reefless->createCookie('viewedNews', json_encode($viewedNews), time() + (365 * 86400));
        $rlCache->updateNewsInBox();
    }

    /**
     * @since 4.7.1 - Added $back_link parameter
     * @since 4.6.0 - $article
     */
    $rlHook->load('newsItem', $article, $back_link);
} else {
    $_SESSION['news_last_viewed_page'] = (int) $page_info['current'];

    $newsCategoryPath = $rlValid->xSql($config['mod_rewrite'] ? $_GET['nvar_1'] : $_GET['category']);
    $newsCategoryPath .= $_GET['listing_id'] && $config['mod_rewrite'] ? '-' . $_GET['listing_id'] : '';
    $newsCategoryID   = $newsCategoryPath ? (int) $rlDb->getOne('ID', "`Path` = '{$newsCategoryPath}'", 'news_categories') : 0;
    $newsCurrentCategory = $newsCategoryID ? $rlNews->getCategories($newsCategoryID) : [];
    $rlSmarty->assign_by_ref('newsCurrentCategory', $newsCurrentCategory);

    if ($newsCurrentCategory) {
        $page_info['name']             = $newsCurrentCategory['Name'];
        $page_info['title']            = $newsCurrentCategory['Title'] ?: $newsCurrentCategory['Name'];
        $page_info['h1']               = $newsCurrentCategory['H1'] ?: $newsCurrentCategory['Name'];
        $page_info['meta_description'] = $newsCurrentCategory['Meta_description'] ?: $page_info['meta_description'];

        $bread_crumbs[] = ['title' => $page_info['title']];
    }

    $news = $rlNews->get(false, true, $page_info['current'], true, false, $newsCategoryID);
    $rlSmarty->assign_by_ref('news', $news);

    // Redirect to first page when no news on the page
    if ($page_info['current'] && !$news) {
        Flynax\Utils\Util::redirect($reefless->getPageUrl('news'));
    }

    $page_info['calc'] = $rlNews->calc_news;

    $rlHook->load('newsList');

    // build rss
    $rss = array(
        'item'  => 'news',
        'title' => $lang['pages+name+' . $pages['news']],
    );
    $rlSmarty->assign_by_ref('rss', $rss);

    unset($blocks['more_news_block']);
    $rlCommon->defineBlocksExist($blocks);
}
