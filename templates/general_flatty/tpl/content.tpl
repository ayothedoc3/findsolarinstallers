<!-- page content -->

{assign var='featured_gallary' value=false}

<div id="wrapper" class="flex-fill position-relative">
    <section id="main_container">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'bread_crumbs.tpl'}

        <div class="inside-container point1 clearfix">
            {rlHook name='tplAbovePageContent'}

            {if $pageInfo.Controller == 'home' && $config.home_page_h1}
                <h1 class="text-center">{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
            {/if}

            <!-- long top blocks area -->
            {if $blocks.long_top && $pageInfo.Key != 'search_on_map'}
                <div class="ling_top_block">
                    {foreach from=$blocks item='block'}
                    {if $block.Side == 'long_top'}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                    {/if}
                    {/foreach}
                </div>
            {/if}
            <!-- long top blocks area -->

            <div class="row{if $pageInfo.Controller == 'listing_details' || $pageInfo.Controller == 'home'} flex-row-reverse{/if}">
                <!-- sidebar area -->
                {if $blocks.left && $pageInfo.Controller != 'listing_details' && $pageInfo.Key != 'search_on_map'}
                    <aside class="left col-lg-{if $pageInfo.Controller == 'listing_details' || !$blocks.left}12{else}4{/if} col-sm-12">
                        {strip}
                        {foreach from=$blocks item='block'}
                        {if $block.Side == 'left'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                        {/if}
                        {/foreach}
                        {/strip}
                    </aside>
                {/if}
                <!-- sidebar area end -->

                <!-- content area -->
                <section id="content" class="col-lg-{if $pageInfo.Controller == 'listing_details' || !$blocks.left || $pageInfo.Key == 'search_on_map'}12{else}8{/if} col-sm-12">
                    {if $pageInfo.Key != 'home' && $pageInfo.Key != 'search_on_map' && $pageInfo.Controller != 'listing_details' && !$no_h1}
                        <div class="h1-nav d-flex mb-4">
                            <h1 class="flex-fill{if ($pageInfo.Key == 'login' || $pageInfo.Login) && !$isLogin} text-center{/if}">{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>

                            {if $navIcons}
                                <nav id="content_nav_icons">
                                    {rlHook name='pageNavIcons'}

                                    {if !empty($navIcons)}
                                        {foreach from=$navIcons item='icon'}
                                            {$icon}
                                        {/foreach}
                                    {/if}
                                </nav>
                            {/if}
                        </div>
                    {/if}

                    {if $pageInfo.Key == 'home'}
                        {assign var='alphabet_array' value=','|explode:$lang.alphabet_characters}
                        {assign var='reduce' value=0}
                        {if 'All'|in_array:$alphabet_array}
                            {assign var='reduce' value=1}
                        {/if}
                        {math assign='char_width' equation='round(100/(count-reduce), 3, 1)' count=$alphabet_array|@count reduce=$reduce}

                        {if $config.category_alphabet_box}
                            <div class="category-alphabet">
                                <ul>
                                    {foreach from=$alphabet_array item='character'}{strip}
                                    {if $character|@mb_strlen:'utf-8' == 1}
                                        <li style="width: {$char_width}%;"><span>{$character}</span></li>
                                    {/if}
                                    {/strip}{/foreach}<li style="width: {$char_width}%;"><span>#</span></li>
                                </ul>

                                <div class="hide" id="cat_alphabet_cont"><div class="loading hide">{$lang.loading}</div></div>
                            </div>
                        {/if}
                    {/if}

                    <div id="system_message">
                        {if $errors || $pNotice || $pAlert}
                            <script class="fl-js-dynamic">
                                var fixed_message = {if $fixed_message}false{else}true{/if};
                                var message_text = '', error_fields = '';
                                var message_type = 'error';
                                {if isset($errors)}
                                    error_fields = {if $error_fields}'{$error_fields|escape:"javascript"}'{else}false{/if};
                                    message_text += '<ul>';
                                    {foreach from=$errors item='error'}message_text += '<li>{$error|regex_replace:"/[\r\t\n]/":"<br />"|escape:"javascript"}</li>';{/foreach}
                                    message_text += '</ul>';
                                {/if}
                                {if isset($pNotice)}
                                    message_text = '{$pNotice|escape:"javascript"}';
                                    message_type = 'notice';
                                {/if}
                                {if isset($pAlert)}
                                    var message_text = '{$pAlert|escape:"javascript"}';
                                    message_type = 'warning';
                                {/if}
                                {literal}
                                $(document).ready(function(){
                                    if (message_text) {
                                        printMessage(message_type, message_text, error_fields, fixed_message);
                                    }
                                });
                            {/literal}</script>
                        {/if}

                        <!-- no javascript mode -->
                        {if !$smarty.const.IS_BOT}
                        <noscript>
                        <div class="warning">
                            <div class="inner">
                                <div class="icon"></div>
                                <div class="message">{$lang.no_javascript_warning}</div>
                            </div>
                        </div>
                        </noscript>
                        {/if}
                        <!-- no javascript mode end -->
                    </div>

                    {if $blocks.top && $pageInfo.Key != 'search_on_map'}
                    <!-- top blocks area -->
                    <aside class="top">
                        {foreach from=$blocks item='block'}
                        {if $block.Side == 'top'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                        {/if}
                        {/foreach}
                    <!-- top blocks area end -->
                    </aside>
                    {/if}

                    <section id="controller_area">{strip}
                        {if $pageInfo.Page_type == 'system'}
                            {include file=$content}
                        {else}
                            <div class="content-padding">{$staticContent}</div>
                        {/if}
                    {/strip}</section>

                    <!-- middle blocks area -->
                    {if $blocks.middle && $pageInfo.Controller != 'listing_details' && $pageInfo.Key != 'search_on_map'}
                    <aside class="middle">
                        {foreach from=$blocks item='block'}
                            {if $block.Side == 'middle'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                        {/foreach}
                    </aside>
                    {/if}
                    <!-- middle blocks area end -->

                    {if ($blocks.middle_left || $blocks.middle_right) && $pageInfo.Key != 'search_on_map'}
                    <!-- middle blocks area -->
                    <aside class="two-middle row">
                        <div class="col-md-6 col-sm-6">
                            {foreach from=$blocks item='block'}
                            {if $block.Side == 'middle_left'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                            {/foreach}
                        </div>

                        <div class="col-md-6 col-sm-6">
                            {foreach from=$blocks item='block'}
                            {if $block.Side == 'middle_right'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                            {/foreach}
                        </div>
                    </aside>
                    <!-- middle blocks area end -->
                    {/if}

                    {if $blocks.bottom && $pageInfo.Key != 'search_on_map'}
                    <!-- bottom blocks area -->
                    <aside class="bottom">
                        {foreach from=$blocks item='block'}
                        {if $block.Side == 'bottom'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                        {/if}
                        {/foreach}
                    </aside>
                    <!-- bottom blocks area end -->
                    {/if}
                </section>
                <!-- content area -->
            </div>
        </div>
    </section>
</div>

<!-- page content end -->
