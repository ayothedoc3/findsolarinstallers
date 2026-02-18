<!-- item out phone messengers tpl -->

{if $item.Opt3
    && ($config.whatsapp_phone_icon || $config.viber_phone_icon || $config.telegram_phone_icon)
    && ($item.Messengers.whatsapp || $item.Messengers.viber || $item.Messengers.telegram)
}
    {getPlainPhoneNumber phone=$item.value assign='messengerPhoneNumber'}

    <span class="messenger-icons d-inline-flex flex-nowrap{if $sidebar} mt-2 mb-2{/if}">
        {if $config.whatsapp_phone_icon && $item.Messengers.whatsapp}
            <a {if $hidden}href="javascript://" data-href="{$_phoneMessengersURLs.whatsapp}"
               {else}href="{$_phoneMessengersURLs.whatsapp}{$messengerPhoneNumber}" target="_blank"{/if}
               class="m{if $smarty.const.RL_LANG_DIR === 'ltr'}r{else}l{/if}-2 hover-brightness-affect messenger-icons__whatsapp"
               title="{if $hidden}{$lang.phone_show_number}{/if}"
            >
                <img src="{$smarty.const.RL_LIBS_URL}icons/messengers/whatsapp.svg?rev={$config.static_files_revision}" alt="">
            </a>
        {/if}

        {if $config.viber_phone_icon && $item.Messengers.viber}
            <a {if $hidden}href="javascript://" data-href="{$_phoneMessengersURLs.viber}"
               {else}href="{$_phoneMessengersURLs.viber}{$messengerPhoneNumber}" target="_blank"{/if}
               class="m{if $smarty.const.RL_LANG_DIR === 'ltr'}r{else}l{/if}-2 hover-brightness-affect messenger-icons__viber"
               title="{if $hidden}{$lang.phone_show_number}{/if}"
            >
                <img src="{$smarty.const.RL_LIBS_URL}icons/messengers/viber.svg?rev={$config.static_files_revision}" alt="">
            </a>
        {/if}

        {if $config.telegram_phone_icon && $item.Messengers.telegram}
            <a {if $hidden}href="javascript://" data-href="{$_phoneMessengersURLs.telegram}"
               {else}href="{$_phoneMessengersURLs.telegram}{$messengerPhoneNumber}" target="_blank"{/if}
               class="hover-brightness-affect messenger-icons__telegram"
               title="{if $hidden}{$lang.phone_show_number}{/if}"
            >
                <img src="{$smarty.const.RL_LIBS_URL}icons/messengers/telegram.svg?rev={$config.static_files_revision}" alt="">
            </a>
        {/if}
    </span>
{/if}

<!-- item out phone messengers tpl end -->
