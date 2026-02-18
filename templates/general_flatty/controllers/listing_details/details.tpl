<!-- listing fields table -->

<div class="listing-fields">
{foreach from=$listing item='group'}
    {assign var='skipGroup' value=false}
    {rlHook name='tplListingDetailsFieldsForeachTop'}

    {if ($noGroupBreak && !$group.Key) || (!$noGroupBreak && $group.Key && $group.Key == $groupBreak) || $skipGroup}
        {continue}
    {/if}

    <div class="{if $group.Key}{$group.Key}{else}no-group{/if}">
        {if $group.Group_ID}
            {assign var='hide' value=false}
            {assign var='group_id' value=false}
            {assign var='group_name' value=false}

            {if !$group.Display}
                {assign var='hide' value=true}
            {/if}

            {assign var='value_counter' value='0'}
            {foreach from=$group.Fields item='group_values' name='groupsF'}
                {if $group_values.value == '' || !$group_values.Details_page}
                    {assign var='value_counter' value=$value_counter+1}
                {/if}
            {/foreach}

            {if !empty($group.Fields) && ($smarty.foreach.groupsF.total != $value_counter)}
                {if $group.Header}
                    {assign var='group_id' value=$group.ID}
                    {assign var='group_name' value=$group.name}
                    {assign var='fieldset_class' value=false}
                {else}
                    {assign var='group_id' value=false}
                    {assign var='group_name' value=false}
                    {assign var='fieldset_class' value='d-none'}
                {/if}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group_id name=$group_name hide=$hide line=$line class=$fieldset_class}

                {if $group.Key == 'location' && $config.map_module && $location.direct}
                    <div class="row{if $locationMode == 'column'} flex-column{/if}">
                        <div class="{if $locationMode == 'column'}col{else}col-md-6{/if} fields">
                            {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                {if !empty($item.value) && $item.Details_page}
                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                {/if}
                            {/foreach}
                        </div>
                        <div class="{if $locationMode == 'column'}col{else}col-md-6 mt-md-0{/if} map mt-3">
                            <section title="{$lang.expand_map}" class="map-capture">
                                <img alt="{$lang.expand_map}"
                                     src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180}"
                                     srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180 scale=2} 2x" />
                                {if !$listing_type.Photo || !$photos}<span class="media-enlarge"><span></span></span>{/if}
                            </section>
                        </div>
                    </div>

                    {if !$listing_type.Photo || !$photos || $tpl_settings.listing_details_simple_gallary}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_static_map.tpl'}
                    {else}
                        <script class="fl-js-dynamic">
                        {literal}

                        $(function(){
                            $('.map .map-capture img').click(function(){
                                flynax.slideTo('.listing-details');
                                $('#media .nav-buttons .nav-button.map').trigger('click');
                            });
                        });

                        {/literal}
                        </script>
                    {/if}
                {else}
                    {if $group.Columns}
                    <div class="row">
                    {/if}
                    {foreach from=$group.Fields item='item' key='field' name='fListings'}
                        {if !empty($item.value) && $item.Details_page}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' columnsView=$group.Columns}
                        {/if}
                    {/foreach}
                    {if $group.Columns}
                    </div>
                    {/if}
                {/if}

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
            {/if}
        {else}
            {if $group.Fields}
                {foreach from=$group.Fields item='item'}
                    {if !empty($item.value) && $item.Details_page}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                    {/if}
                {/foreach}
            {/if}
        {/if}
    </div>
{/foreach}
</div>

<!-- listing fields table end -->
