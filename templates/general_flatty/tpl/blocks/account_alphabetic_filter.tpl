<!-- alphabetic account search -->

{assign var='at_page_key' value='at_'|cat:$account_type.Key}

{strip}
<div class="alphabetic-saerch">
    <div>
        {foreach from=$alphabet item='character' name='alphaF'}
            {if $smarty.foreach.alphaF.iteration == 1}
                {pageUrl key=$at_page_key assign='characterUrl'}
            {else}
                {if $config.mod_rewrite}
                    {pageUrl key=$at_page_key add_url=$character assign='characterUrl'}
                {else}
                    {pageUrl key=$at_page_key vars="character=`$character`" assign='characterUrl'}
                {/if}
            {/if}

            <a href="{$characterUrl}"
                class="{if $character == $char}active{/if}{if $smarty.foreach.alphaF.iteration == 1 || $smarty.foreach.alphaF.iteration == 2} wide{/if}">
                {$character}
            </a>
        {/foreach}
    </div>
</div>
{/strip}

<!-- alphabetic account search end -->
