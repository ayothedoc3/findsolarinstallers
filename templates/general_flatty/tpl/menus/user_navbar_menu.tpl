{if $isLogin}
    <ul class="account-menu-content">
        {foreach from=$account_menu item='mItem'}
            {assign var='accountMenuHref' value=''}
            {if $mItem.Page_type == 'external'}
                {assign var='accountMenuHref' value=$mItem.Controller}
            {else}
                {pageUrl key=$mItem.Key vars=$mItem.Get_vars assign='accountMenuHref'}
            {/if}

            {if $mItem.Key == 'my_messages' && !$config.messages_module}{else}
                <li class="d-flex align-items-center justify-content-between position-relative{if $mItem.Key == 'my_messages' && $new_messages} messages{/if}">
                    {if $smarty.const.IS_ESCORT === true}
                        <a{if $page == $mItem.Path} class="active"{/if}
                           title="{strip}
                                {if $account_info.Escort_Type == 'personal'
                                    && ($mItem.Key == 'add_listing' || $mItem.Controller == 'my_listings')
                                }
                                    {$lang.escort_my_profile}
                                {else}
                                    {$mItem.title}
                                {/if}{/strip}"
                           href="{$accountMenuHref}"
                        >
                            {if $account_info.Escort_Type == 'personal'
                                && ($mItem.Key == 'add_listing' || $mItem.Controller == 'my_listings')
                            }
                                {$lang.escort_my_profile}
                            {else}
                                {$mItem.name}
                            {/if}
                        </a>
                    {else}
                        <a{if $page == $mItem.Path} class=" active"{/if}
                           title="{$mItem.title}"
                           href="{$accountMenuHref}"
                        >
                            {$mItem.name}
                        </a>
                    {/if}

                    {if $mItem.Key == 'my_messages' && $new_messages}
                        <a class="counter ml-4 mr-0"
                           title="{$lang.new_message_available|replace:'[count]':$new_messages}"
                           href="{pageUrl key='my_messages'}"
                        >
                            {$new_messages}
                        </a>
                    {/if}
                </li>
            {/if}
        {/foreach}

        <li>
            <a title="{$lang.title_logout}"
               href="{pageUrl key='login' vars='action=logout'}"
               class="logout"
            >
                {$lang.logout}
            </a>
        </li>
    </ul>

    {rlHook name='tplAfterAccountMenu'}
{else}
    {include file='blocks/login_modal.tpl' linkLabels=true}
{/if}
