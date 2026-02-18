<!-- featured listings block -->

{rlHook name='featuredTop'}

{assign var='page_key' value=$listing_types.$type.Page_key}

{if !empty($listings)}
    {if !$type}
        {assign var='direct_type' value=true}
    {/if}

    <ul class="row featured clearfix{if !$type || $listing_types.$type.Photo} with-pictures{else} list{/if}">{strip}
    {foreach from=$listings item='featured_listing' key='key' name='listingsF'}
        {if $direct_type}
            {assign var='type' value=$featured_listing.Listing_type}
        {/if}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl'}
    {/foreach}
    {/strip}</ul>
{else}
    {if $pages.add_listing}
        {pageUrl key='add_listing' assign='add_listing_href'}
        {assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
        {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
    {else}
        {phrase key='no_listings_found_deny_posting' db_check='true'}
    {/if}
{/if}

<!-- featured listings block end -->
