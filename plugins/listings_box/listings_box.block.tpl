<!-- listings boxes -->

{rlHook name='featuredTop'}

{assign var='has_pictures' value=false}
{assign var='box_types' value=','|explode:$type}
{foreach from=$box_types item='box_type'}
    {if $listing_types.$box_type.Photo}
        {assign var='has_pictures' value=true}
        {break}
    {/if}
{/foreach}

{if !empty($listings_box)}
    <ul id="listing_box_{$block.ID}" class="row featured{if $box_option.display_mode == 'grid'} lb-box-grid{/if}{if !$type || $has_pictures} with-pictures{else} list{/if}">
    {foreach from=$listings_box item='featured_listing' key='key' name='listingsF'}{strip}
        {assign var='type' value=$featured_listing.Listing_type}
        {assign var='page_key' value=$listing_types.$type.Page_key}
        {if $box_option.display_mode == 'default'}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl'}
        {elseif $box_option.display_mode == 'grid'}
            {include file=$smarty.const.RL_PLUGINS|cat:'listings_box'|cat:$smarty.const.RL_DS|cat:'listings_box.grid.tpl'}
        {/if}
    {/strip}{/foreach}
    </ul>

    {if $box_option.load_more && $listings_box|@count >= $box_option.limit}
        <div class="text-center" id="ads-block-{$block.ID}">
            <input class="pl-5 pr-5" type="button" value="{$lang.load_more_listings}" data-phrase="{$lang.load_more_listings}" />
        </div>

        <script class="fl-js-dynamic">
        {literal}

        $(function(){
            var box_id  = 'ads-block-{/literal}{$block.ID}{literal}';
            var $cont   = $('#' + box_id);
            var $box    = $cont.prev();
            var $button = $cont.find('input[type=button]');

            var data = {
                {/literal}
                mode: 'lbLoadMoreListings',
                key: '{$block.Key|replace:'ltfb_':''}',
                ids: '{$lb_selected_ids}',
                sideBarExists: {if $side_bar_exists}1{else}0{/if},
                blockSide: '{$block.Side}',
                pageKey: rlPageInfo['key']
                {literal}
            };

            $button.width($button.width());

            $button.click(function(){
                $(this).val(lang['loading']);

                flUtil.ajax(data, function(response, status){
                    if (status == 'success' && response.status == 'OK') {
                        if (response.results.html) {
                            var $html = $(jQuery.parseHTML(response.results.html)[2]);
                            $listings = $html.find('> li').unwrap();

                            if (typeof $.convertPrice == 'function') {
                                $listings.find('.price_tag > *:not(nav)').each(function(){
                                    $(this).convertPrice();
                                });
                            }

                            $box.append($listings);

                            flFavoritesHandler();
                            flContactOwnerHandler();

                            if (response.results.next) {
                                data.total += parseInt(response.results.count);
                                data.ids += ',' + response.results.ids;
                            } else {
                                $cont.remove();
                            }
                        } else {
                            $cont.remove();
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }

                    $button.val($button.data('phrase'));
                });
            });
        });

        {/literal}
        </script>
    {/if}
{else}
    {if $pages.add_listing}
        {pageUrl key='add_listing' assign='add_listing_href'}
        {assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
        {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
    {else}
        {phrase key='no_listings_found_deny_posting' db_check='true'}
    {/if}
{/if}
<!-- listings boxes end -->
