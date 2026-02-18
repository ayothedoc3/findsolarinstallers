
<div id="categories_{$mode}" style="margin: 0 0 8px;">
    <div class="tree" data-key="{$mode}">
        {foreach from=$sections item='section'}
            <fieldset class="light" data-key='{$section.Key}'>
                <legend id="legend_box{$section.ID}" class="up" onclick="fieldset_action('{$mode}_{$section.ID}');">{$section.name}</legend>
                <div id="{$mode}_{$section.ID}">
                    {if !empty($section.Categories)}
                        {include file=$smarty.const.RL_PLUGINS|cat:'listings_box'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'category_tree.tpl' categories=$section.Categories first=true mode=$mode}
                    {else}
                        <div style="padding: 0 0 8px 10px;">{$lang.no_items_in_sections}</div>
                    {/if}
                </div>
            </fieldset>
        {/foreach}
    </div>
</div>

{assign var='subCatName' value='subcategories'}
{if $mode == 'cats'}
    {assign var='subCatName' value='use_subcats'}
{/if}
<div style="padding: 0 0 6px 20px;">
    <input id="{$subCatName}" {if !empty($sPost.$subCatName)}checked="checked"{elseif $mode == 'cats' && empty($sPost.$subCatName)}checked="checked"{/if} type="checkbox" name="{$subCatName}" value="1" />
    <label class="cLabel" for="{$subCatName}">{$lang.include_subcats}</label>
</div>

{if $check_all}
    <div class="grey_area">
        <label><input class="checkbox" {if $sPost.cat_sticky}checked="checked"{/if} type="checkbox" name="cat_sticky" value="true" /> {$lang.sticky}</label>
        <span id="cats_nav" {if $sPost.cat_sticky}class="hide"{/if}>
            <span onclick="$('#categories_{$mode} div.tree input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
            <span class="divider"> | </span>
            <span onclick="$('#categories_{$mode} div.tree input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
        </span>
    </div>
{/if}

{assign var='parent' value=$mode|cat:'_parent'}
<script type="text/javascript">
{literal}
$(document).ready(function(){
    var catsSelected = [];
    var catsSelectedParent = [];
    catsSelected = {/literal}{if $smarty.post.$mode}[{foreach from=$smarty.post.$mode item='post_cat' name='postcatF'}'{$post_cat}'{if !$smarty.foreach.postcatF.last},{/if}{/foreach}]{else}false{/if}{literal};
    catsSelectedParent = {/literal}{if $smarty.post.$parent}[{foreach from=$smarty.post.$parent item='post_cat' name='postcatF'}'{$post_cat}'{if !$smarty.foreach.postcatF.last},{/if}{/foreach}]{else}false{/if}{literal};
    customTree.init({/literal}'{$mode}'{literal}, catsSelected, catsSelectedParent);
});
{/literal}
</script>