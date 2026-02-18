<!-- item out value tpl -->

{if $item.Type == 'checkbox' && is_array($item.Values)}
    <ul class="checkboxes row{if $item.Opt1} mt-2{/if}">
    {if $item.Opt2}{math assign='col_count' equation='12 / opt' opt=$item.Opt2}{/if}
    {foreach from=$item.Values item='tile' name='checkboxF'}
        {if !empty($item.Condition)}
            {assign var="tit_source" value=$tile.Key}
        {else}
            {assign var="tit_source" value=$tile.ID}
        {/if}
        {if $item.Opt1 || $tit_source|in_array:$item.source}
            <li title="{$lang[$tile.pName]}" class="{if $col_count}col-sm-6 col-md-{$col_count}{else}col-lg-4 col-md-4 col-sm-6{/if} {if $tit_source|in_array:$item.source}active{/if}">
                <svg viewBox="0 0 17 12" class="mr-2{if $tit_source|in_array:$item.source} details-icon-fill{/if}">
                   <use xlink:href="#core-icon-tick"></use>
                </svg>
                {$lang[$tile.pName]}
            </li>
        {/if}
    {/foreach}
    </ul>
{elseif $item.Type == 'file'}
    {if $item.Opt1}
        <div class="uploaded-files d-flex flex-wrap">
            {foreach from=','|explode:$item.value item='file'}
                {assign var='file_info' value=$file|pathinfo}
                <a href="{$smarty.const.RL_FILES_URL}{$file}" target="_blank" class="d-flex flex-column uploaded-file mr-3">
                    <span class="uploaded-file__ext d-flex flex-column justify-content-center align-items-center hlight hborder">
                        {$file_info.extension}
                    </span>
                    <span class="font-size-xs mb-3">
                        <span class="d-flex mt-1 text-center text-break justify-content-center">{$file_info.filename}</span>
                    </span>
                </a>
            {/foreach}
        </div>
    {else}
        <a href="{$smarty.const.RL_FILES_URL}{$item.value}">{$lang.download}</a>
    {/if}
{elseif $item.Type == 'phone'}
    {if $item.Contact && $item.fake}
        <span>{$item.value}</span>
    {else}
        {if $item.Hidden}
            {if $item.source && $listing_data && $listing_data.ID}
                {assign var='entityID' value=$listing_data.ID}
                {assign var='entity' value='listing'}
            {elseif !$item.source && $seller_info && $seller_info.ID}
                {assign var='entityID' value=$seller_info.ID}
                {assign var='entity' value='account'}
            {/if}

            <div class="hidden-phone"
                 data-entity="{$entity}"
                 data-entity-id="{$entityID}"
                 data-field="{$item.Key}"
                 data-listing-id="{if $listing_data && $listing_data.ID}{$listing_data.ID}{/if}"
            >
                <span class="mr-3">{$item.value}</span>
                {include file='blocks/field_out_phone_messengers.tpl' hidden=true}
            </div>
            <div class="show-phone{if $entity === 'account' && $config.hidden_phone_numbers > 0} mt-1 mb-1{/if}">
                <a title="{$lang.phone_show_number}" href="javascript://">{$lang.phone_show_number}</a>
            </div>
        {else}
            <span class="m{if $smarty.const.RL_LANG_DIR === 'ltr'}r{else}l{/if}-3">
                <a href="tel:{$item.value}">{$item.value}</a>
            </span>

            {include file='blocks/field_out_phone_messengers.tpl'}
        {/if}
    {/if}
{elseif $item.Condition == 'isEmail'}
    {if $item.Contact && $item.fake}<span>{$item.value}</span>{else}<a href="mailto:{$item.value}">{$item.value}</a>{/if}
{elseif $item.Condition == 'isUrl'}
    {if $item.Contact && $item.fake}<span>{$item.value}</span>{else}<a rel="nofollow" href="{$item.value}" target="_blank">{$item.value|replace:'http://':''|replace:'https://':''|truncate:35:'..':true:true}</a>{/if}
{else}
    {$item.value}
{/if}

<!-- item out value tpl end -->
