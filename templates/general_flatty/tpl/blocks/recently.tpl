<!-- recently listings area -->
{assign var='partition' value=$listing_types.$lt_key.Page_key}

{if $listings}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' periods=true}

    {if $listing_types|@count > 1}
        {if $config.mod_rewrite}
            {assign var="type_url" value=$pages.$partition}
        {else}
            {assign var="type_url" value='type='|cat:$pages.$partition}
        {/if}
    {/if}

    <!-- paging block -->
    {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$type_url}
    <!-- paging block end -->
{else}
    <div class="text-notice">
        {if $pages.add_listing}
            {pageUrl key='add_listing' assign='add_listing_href'}
            {assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
            {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
        {else}
            {phrase key='no_listings_found_deny_posting' db_check='true'}
        {/if}
    </div>
{/if}

<!-- recently listings area end -->
