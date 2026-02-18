<!-- menus/footer_social_icons.tpl -->

{assign var='network_replace' value=`$smarty.ldelim`network_name`$smarty.rdelim`}

{assign var='icon_margin_class' value='ml-4'}
{if $marginClass}
    {assign var='icon_margin_class' value=$marginClass}
{/if}

{if $config.telegram_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'Telegram'}" href="{$config.telegram_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-telegram"></use>
        </svg>
    </a>
{/if}
{if $config.vk_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'VK'}" href="{$config.vk_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-vk"></use>
        </svg>
    </a>
{/if}
{if $config.whatsapp_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'Whatsapp'}" href="{$config.whatsapp_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-whatsapp"></use>
        </svg>
    </a>
{/if}
{if $config.instagram_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'Instagram'}" href="{$config.instagram_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-instagram"></use>
        </svg>
    </a>
{/if}
{if $config.facebook_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'Facebook'}" href="{$config.facebook_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-facebook"></use>
        </svg>
    </a>
{/if}
{if $config.twitter_page}
    <a class="{$icon_margin_class}" target="_blank" title="{$lang.join_us_on_social_network|replace:$network_replace:'Twitter'}" href="{$config.twitter_page}">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-twitterx"></use>
        </svg>
    </a>
{/if}
{if $pages.rss_feed}
    <a class="{$icon_margin_class}" title="{$lang.subscribe_rss}" href="{getRssUrl mode='footer'}" target="_blank">
        <svg class="d-block" viewBox="0 0 24 24">
           <use xlink:href="#core-social-rss"></use>
        </svg>
    </a>
{/if}

<!-- menus/footer_social_icons.tpl end -->
