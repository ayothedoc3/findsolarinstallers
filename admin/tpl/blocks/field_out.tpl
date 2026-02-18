<!-- listing field output tpl -->

<tr id="df_field_{$item.Key}">
    <td class="name">{$item.name}:</td>
    <td class="value {if $smarty.foreach.fListings.first}first{/if}">
        {if $item.Type == 'checkbox'}
            {if $item.Opt1}
                {if $item.Opt2}
                    {assign var='col_num' value=$item.Opt2}
                {else}
                    {assign var='col_num' value=3}
                {/if}
                <table class="checkboxes{if $col_num > 2} fixed{/if}">
                <tr>
                {foreach from=$item.Values item='tile' name='checkboxF'}
                    <td>
                        {if !empty($item.Condition)}
                            {assign var='tile_source' value=$tile.Key}
                        {else}
                            {assign var='tile_source' value=$tile.ID}
                        {/if}
                        <div title="{$lang[$tile.pName]}" class="checkbox{if $tile_source|in_array:$item.source}_active{/if}">
                        {if $tile_source|in_array:$item.source}<img src="{$rlTplBase}img/blank.gif" alt="" />{/if}
                        {$lang[$tile.pName]}
                        </div>
                    </td>
                    {if $smarty.foreach.checkboxF.iteration%$col_num == 0 && !$smarty.foreach.checkboxF.last}
                    </tr>
                    <tr>
                    {/if}
                {/foreach}
                </tr>
                </table>
            {else}
                {$item.value}
            {/if}
        {elseif $item.Type === 'phone'}
            <span class="mr-3">
                <a href="tel:{$item.value}">{$item.value}</a>
            </span>

            {include file='blocks/field_out_phone_messengers.tpl'}
        {elseif $item.Type == 'file'}
            {if $item.Opt1}
                <div class="uploaded-files">
                    {foreach from=','|explode:$item.value item='file'}
                        {assign var='file_info' value=$file|pathinfo}
                        <div style="margin-bottom: 5px;">
                            <a href="{$smarty.const.RL_FILES_URL}{$file}" target="_blank" class="d-flex flex-column uploaded-file mr-3">
                                {$file_info.basename}
                            </a>
                        </div>
                    {/foreach}
                </div>
            {else}
                <a href="{$smarty.const.RL_FILES_URL}{$item.value}">{$lang.download}</a>
            {/if}
        {else}
            {if $item.Type == 'price' && $item.Opt1 && $item.Options.from}{$lang.price_from}{/if}
            {$item.value}
        {/if}
    </td>
</tr>

<!-- listing field output tpl end -->
