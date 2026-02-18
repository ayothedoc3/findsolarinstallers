{strip}
{if $itemTag}<{$itemTag}{if $pageInfo.Key == $menuItem.Key} class="active"{/if}>{/if}
    <a data-key="{$menuItem.Key}" {if !$itemTag} class="{$itemClass}{if $pageInfo.Key == $menuItem.Key} active{/if}"{/if}{if $menuItem.No_follow || $menuItem.Login} rel="nofollow"{/if} title="{$menuItem.title}" href="
    {if $menuItem.Page_type == 'external'}
        {$menuItem.Controller}
    {else}
        {assign var='item_path' value=''}
        {assign var='item_vars' value=$menuItem.Get_vars}
        {if $pageInfo.Controller != 'add_listing' && $menuItem.Controller == 'add_listing' && $category.ID && !$category.Lock}
            {assign var='item_path' value='step='|cat:$steps.plan.path}
            {assign var='item_vars' value='id='|cat:$category.ID}
        {/if}
        {pageUrl key=$menuItem.Key vars=$item_vars add_url=$item_path}
    {/if}
    ">{if $itemIcon}<span class="icon-opacity__icon"></span>{/if}{$menuItem.name}</a>
{if $itemTag}</{$itemTag}>{/if}
{/strip}
