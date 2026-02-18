<!-- listing details navigation -->

<div class="icons">{strip}
    {if $listing_data.Account_ID == $account_info.ID}
        {if $listing_data.Status == 'incomplete'}
            {pageUrl key='add_listing' vars='incomplete='|cat:$listing_data.ID assign='edit_page_url'}
        {else}
            {pageUrl key='edit_listing' vars='id='|cat:$listing_data.ID assign='edit_page_url'}
        {/if}
        <a class="button low" href="{$edit_page_url}">{$lang.edit_listing}</a>
    {else}
        {rlHook name='listingDetailsNavIcons'}

        {if $listing_data.Status == 'active'}
            <a rel="nofollow" target="_blank" href="{pageUrl key='print' vars='item=listing&id='|cat:$listing_data.ID}" title="{$lang.print_page}" class="print"><span></span></a>
        {/if}

        {if !$noFavIcon}
            <span id="fav_{$listing_data.ID}" class="favorite add" title="{$lang.add_to_favorites}"><span class="icon"></span></span>
        {/if}
    {/if}
{/strip}</div>

<!-- listing details navigation end -->
