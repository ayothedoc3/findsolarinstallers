<!-- search form block -->

<section class="side_block_search">
    {assign var='spage_key' value=$listing_types[$form_type_key].Page_key}

    <form method="{$listing_types[$form_type_key].Submit_method}" action="{pageUrl key=$spage_key add_url=$search_results_url}">
        <input type="hidden" name="form" value="{$form_key}" />
        <input type="hidden" name="action" value="search" />

        {foreach from=$hidden_fields item='hField_val' key='hField_key'}
        <input type="hidden" name="f[{$hField_key}]" value="{$hField_val}" />
        {/foreach}

        {foreach from=$form item='group'}{strip}
            {if $group|@is_array}
                {if $group.Group_ID}
                    {if $group.Fields && $group.Display}
                        {assign var='hide' value=false}
                    {else}
                        {assign var='hide' value=true}
                    {/if}

                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.Group_ID name=$lang[$group.pName]}
                        {if $group.Fields}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                        {else}
                            {$lang.no_items_in_group}
                        {/if}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                {elseif $group.Fields}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                {/if}
            {/if}
        {/strip}{/foreach}

        {if $group|@is_array && $group.With_picture}
            <div class="search-item">
                <label>
                    <input name="f[with_photo]" type="checkbox" value="true" />
                    {$lang.with_photos_only}
                </label>
            </div>
        {/if}

        <input type="submit" name="search" value="{$lang.search}" />
    </form>
</section>

<!-- search form block -->
