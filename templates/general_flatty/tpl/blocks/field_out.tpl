<!-- field output tpl -->

{assign var='customField' value=false}
{customViewDetailsFieldHandler field=$item}

{if !$customField}
    <div class="{strip}table-cell clearfix
            {if $columnsView} col-md-6 two-columns{/if}
            {if $small} small{/if}
            {if ($item.Type == 'checkbox' && $item.Opt1) || $item.Type == 'textarea'} wide-field
                {if $item.Type == 'textarea'} textarea{/if}
            {/if}
            {if $item.Type == 'phone'} phone{/if}{/strip}"
        id="df_field_{$item.Key}">
        {if $item.Type == 'image' && $small}{else}
            <div class="name" title="{$item.name}">{if $item.name}{$item.name}{else}{$lang[$item.pName]}{/if}</div>
        {/if}
        <div class="value{if $item.Type == 'image'} image{/if}">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out_value.tpl'}
        </div>
    </div>
{/if}

<!-- field output tpl end -->
