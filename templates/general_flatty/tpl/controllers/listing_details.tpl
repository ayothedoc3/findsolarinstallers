<!-- listing details -->

{if !$errors}

{if $config.map_module && $location.direct}
    {mapsAPI assign='mapAPI'}

    <script>
    rlConfig['mapAPI'] = [];
    rlConfig['mapAPI']['css'] = JSON.parse('{$mapAPI.css|@json_encode}');
    rlConfig['mapAPI']['js']  = JSON.parse('{$mapAPI.js|@json_encode}');
    </script>
{/if}

<aside class="right">
    {if !$pageInfo.Listing_details_inactive}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_sidebar.tpl'}
    {/if}

    {strip}
    {foreach from=$blocks item='block'}
    {if $block.Side == 'left'}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
    {/if}
    {/foreach}
    {/strip}
</aside>

<div class="listing-details">
    {rlHook name='listingDetailsTopTpl'}

    <div class="d-flex flex-wrap flex-md-nowrap top-navigation mb-3">
        <div class="d-flex flex-fill flex-nowrap align-items-baseline mr-0 mr-md-3 mb-2 mb-md-0">
            <div class="d-flex justify-content-between">
                {rlHook name='tplListingDetailsNavLeft'}
                {rlHook name='tplListingDetailsNavRight'}
            </div>
            <h1 class="flex-fill">{$pageInfo.name}</h1>
        </div>
        {include file=$controllerDir|cat:'listing_details/navigation.tpl'}
    </div>

    <section class="d-flex flex-column flex-md-row main-section{if !$photos} no-picture{/if}">
        {if $photos}
            {include file=$componentDir|cat:'listing-details-gallery/_listing-details-gallery.tpl' noNavBar=true}
        {/if}

        <div class="details d-flex flex-column flex-fill{if $photos} ml-md-4{/if}">
            <div>
                {rlHook name='tplListingDetailsRating'}
            </div>

            <div class="top-navigation">
                <!-- price tag -->
                {if $price_tag.value}
                    <div class="price-tag" id="df_field_price">
                        {if $price_tag.Options.from}{$lang.price_from}{/if}
                        <span>
                            {if $price_contact_form}
                                <a href="javascript://"
                                    class="contact-owner price-contact-form"
                                    data-listing-id="{$listing_data.ID}"
                                    data-account-id="{$listing_data.Account_ID}"
                                >
                            {/if}
                            {$price_tag.value}
                            {if $price_contact_form}</a>{/if}
                        </span>
                        {if $listing_data.sale_rent == 2 && $listing.common.Fields.time_frame.value}
                            / {$listing.common.Fields.time_frame.value}
                        {/if}
                    </div>
                {/if}
                <!-- price tag end -->
            </div>

            {if $listing_type.Photo && $photos|@count > 0}
                <div class="content-padding flex-fill">
                    {assign var='main_section_group_break' value=false}
                    {assign var='main_section_no_group' value=false}

                    {foreach from=$listing item='group'}
                        {if $group.Group_ID}
                            {if !empty($group.Fields)}
                                {if $main_section_no_group}
                                    {break}
                                {/if}

                                {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                    {if !empty($item.value) && $item.Details_page}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                    {/if}
                                {/foreach}

                                {assign var='main_section_group_break' value=$group.Key}
                            {/if}
                        {else}
                            {if $group.Fields}
                                {foreach from=$group.Fields item='item'}
                                    {if !empty($item.value) && $item.Details_page}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                    {/if}
                                {/foreach}

                                {assign var='main_section_no_group' value=true}
                            {/if}
                        {/if}

                        {if $main_section_group_break}{break}{/if}
                    {/foreach}
                </div>
            {/if}

            <div class="plugins-inline">{rlHook name='listingDetailsBeforeStats'}</div>
        </div>
    </section>

    <section class="content-section clearfix">
        <!-- tabs -->
        {if $tabs|@count > 1}
            <ul class="tabs tabs-hash">
                {foreach from=$tabs item='tab' name='tabF'}{strip}
                    <li {if $smarty.foreach.tabF.first}class="active"{/if} id="tab_{$tab.key}">
                        <a href="#{$tab.key}" data-target="{$tab.key}">{$tab.name}</a>
                    </li>
                {/strip}{/foreach}
            </ul>
        {/if}
        <!-- tabs end -->

        <!-- tabs content -->

        <!-- listing details -->
        <div id="area_listing" class="tab_area">
            {rlHook name='listingDetailsPreFields'}

            <div class="content-padding">
                {include file=$controllerDir|cat:'listing_details/details.tpl' groupBreak=$main_section_group_break noGroupBreak=$main_section_no_group}
            </div>

            <div class="content-padding">
                {include file=$controllerDir|cat:'listing_details/statistics.tpl'}
            </div>
        </div>
        <!-- listing details end -->

        {if $config.tell_a_friend_tab}
            <!-- tell a friend tab -->
            <div id="area_tell_friend" class="tab_area hide">
                <div class="content-padding">
                    <div class="submit-cell">
                        <div class="name">{$lang.friend_name} <span class="red">*</span></div>
                        <div class="field"><input class="wauto" type="text" id="friend_name" name="friend_name" maxlength="50" size="30" value="{$smarty.post.friend_name}" /></div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">{$lang.friend_email} <span class="red">*</span></div>
                        <div class="field"><input class="wauto" type="text" id="friend_email" name="friend_email" maxlength="50" size="30" value="{$smarty.post.friend_email}" /></div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">{$lang.your_name}</div>
                        <div class="field"><input class="wauto" type="text" id="your_name" name="your_name" maxlength="100" size="30" value="{$account_info.Full_name}" /></div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">{$lang.your_email}</div>
                        <div class="field"><input class="wauto" type="text" id="your_email" name="your_email" maxlength="30" size="30" value="{$account_info.Mail}" /></div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">{$lang.message}</div>
                        <div class="field"><textarea id="message" name="message" rows="6" cols="50">{$smarty.post.message}</textarea></div>
                    </div>

                    {if $config.security_img_tell_friend}
                    <div class="submit-cell">
                        <div class="name">{$lang.security_code} <span class="red">*</span></div>
                        <div class="field">
                            {include file='captcha.tpl' no_caption=true}
                        </div>
                    </div>
                    {/if}

                    <div class="submit-cell buttons">
                        <div class="name"></div>
                        <div class="field"><input onclick="xajax_tellFriend($('#friend_name').val(), $('#friend_email').val(), $('#your_name').val(), $('#your_email').val(), $('#message').val(), $('#security_code').val(), '{$print.id}');$(this).val('{$lang.loading}');" type="button" name="finish" value="{$lang.send}" /></div>
                    </div>
                </div>
            </div>
            <!-- tell a friend tab end -->
        {/if}

        {rlHook name='listingDetailsBottomTpl'}

        <!-- tabs content end -->

        <!-- middle blocks area -->
        {if $blocks.middle}
        <aside class="middle">
            {foreach from=$blocks item='block'}
                {if $block.Side == 'middle'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                {/if}
            {/foreach}
        </aside>
        {/if}
        <!-- middle blocks area end -->

    </section>

    {if !$allow_photos}
        <div id="picture_locked_mobile" class="hide">
            <div class="tmp-dom">
                <div class="restricted-content">
                {if $isLogin}
                    <p class="picture-hint hide">{$lang.view_picture_not_available}</p>
                    <p class="video-hint hide">{$lang.watch_video_not_available}</p>
                    <span>
                        <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                    </span>
                {else}
                    <p class="picture-hint hide">{$lang.view_picture_hint}</p>
                    <p class="video-hint hide">{$lang.watch_video_hint}</p>
                    <span>
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'login_modal.tpl'}
                    </span>
                {/if}
                </div>
            </div>
        </div>
    {/if}

    <script class="fl-js-dynamic">
    {if isset($smarty.get.highlight)}
        flynaxTpl.highlightResults("{$smarty.session.keyword_search_data.keyword_search}", true);
    {/if}

    var ld_inactive = {if $pageInfo.Listing_details_inactive}'{$lang.ld_inactive_notice}'{else}false{/if};

    {literal}
        if ($('#df_field_vin .value').length > 0) {
            var html = '<a style="font-size: 14px;" href="javascript:void(0);">{/literal}{if $lang.check_vin}{$lang.check_vin}{else}Check Vin{/if}{literal}</a>';
            var vin = trim( $('#df_field_vin .value').text() );
            var frame = '<iframe scrolling="auto" height="600" frameborder="0" width="100%" src="http://www.carfax.com/cfm/check_order.cfm?vin='+vin+'" style="border: 0pt none;overflow-x: hidden; overflow-y: auto;background: white;"></iframe>';
            var source = '';
        }
    {/literal}
    </script>

    {rlHook name='listingDetailsBottomJs'}

    <script class="fl-js-dynamic">
    {literal}
    $(document).ready(function(){
        if ( ld_inactive ) {
            printMessage('warning', ld_inactive, false, true);
        }

        flynaxTpl.setupTextarea('div.seller-short');

        $('.contact-seller').flModal({
            source: '#contact_owner_form',
            width: 400,
            height: 'auto',
            ready: function(){
                flynaxTpl.setupTextarea('div.modal_content');
            }
        });

        $('#df_field_vin .value').append(html);

        $('#df_field_vin .value a').flModal({
            content: frame,
            source: source,
            width: 900,
            height: 640
        });
    });
    {/literal}
    </script>

</div>
{else}
    <!-- TODO HERE -->
{/if}

<!-- listing details end -->
