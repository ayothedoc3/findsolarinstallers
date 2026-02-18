<!-- category level -->

{if $categories}
    <ul {if $first}class="first"{/if}>
        {foreach from=$categories item='cat' name='catF'}
            {if !empty($cat.Sub_cat) || ($cat.Add == '1' && $listing_types[$cat_type].Cat_custom_adding)}{assign var='sub_leval' value=true}{else}{assign var='sub_leval' value=false}{/if}
            <li class="tree_cat {if $cat.Lock}locked{/if}" data-index='{$cat.ID}'>
                <img {if !$sub_leval}class="no_child"{/if} src="{$rlTplBase}img/blank.gif" alt="" />
                <label><input type="checkbox" name="{$mode}[]" value="{$cat.ID}" /> <span>{$cat.name}</span></label>
                <span class="tree_loader"></span>
            </li>
        {/foreach}
    </ul>
{/if}

<!-- category level end -->
