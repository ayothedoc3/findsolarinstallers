<!-- listing fields table - Solar Override -->

<div class="listing-fields solar-listing-fields">

{* === Google Rating Display === *}
{assign var='rating_val' value=false}
{assign var='reviews_val' value=false}
{foreach from=$listing item='group'}
    {if $group.Fields}
        {foreach from=$group.Fields item='item'}
            {if $item.Key == 'google_rating' && $item.value}{assign var='rating_val' value=$item.value}{/if}
            {if $item.Key == 'total_reviews' && $item.value}{assign var='reviews_val' value=$item.value}{/if}
        {/foreach}
    {/if}
{/foreach}

{if $rating_val}
<div class="solar-rating-banner">
    <div class="solar-stars-large" style="--rating: {$rating_val}"></div>
    <span class="solar-rating-number">{$rating_val}</span>
    {if $reviews_val}
        <span class="solar-review-text">Based on {$reviews_val} Google reviews</span>
    {/if}
</div>
{/if}

{* === Certification Badges === *}
{assign var='certs_val' value=false}
{foreach from=$listing item='group'}
    {if $group.Fields}
        {foreach from=$group.Fields item='item'}
            {if $item.Key == 'certifications' && $item.value}{assign var='certs_val' value=$item.value}{/if}
        {/foreach}
    {/if}
{/foreach}

{if $certs_val}
<div class="solar-cert-badges">
    {* The value is already formatted by Flynax as comma-separated labels *}
    {assign var='cert_items' value=","|explode:$certs_val}
    {foreach from=$cert_items item='cert'}
        <span class="solar-cert-badge">{$cert|trim}</span>
    {/foreach}
</div>
{/if}

{* === Get a Free Quote CTA === *}
<div class="solar-quote-cta">
    <a href="javascript://" class="solar-btn-primary contact-seller">Get a Free Quote</a>
    {assign var='phone_val' value=false}
    {foreach from=$listing item='group'}
        {if $group.Fields}
            {foreach from=$group.Fields item='item'}
                {if $item.Key == 'company_phone' && $item.value}{assign var='phone_val' value=$item.value}{/if}
            {/foreach}
        {/if}
    {/foreach}
    {if $phone_val}
        <a href="tel:{$phone_val|regex_replace:'/[^0-9+]/':''}" class="solar-btn-outline solar-phone-btn">
            Call: {$phone_val}
        </a>
    {/if}
</div>

{* === Standard Field Groups (with solar styling) === *}
{foreach from=$listing item='group'}
    {assign var='skipGroup' value=false}
    {rlHook name='tplListingDetailsFieldsForeachTop'}

    {if ($noGroupBreak && !$group.Key) || (!$noGroupBreak && $group.Key && $group.Key == $groupBreak) || $skipGroup}
        {continue}
    {/if}

    {* Skip rating/reviews/certs from standard display - we already showed them above *}
    <div class="{if $group.Key}{$group.Key}{else}no-group{/if} solar-field-group">
        {if $group.Group_ID}
            {assign var='hide' value=false}
            {assign var='group_id' value=false}
            {assign var='group_name' value=false}

            {if !$group.Display}
                {assign var='hide' value=true}
            {/if}

            {assign var='value_counter' value='0'}
            {assign var='total_fields' value='0'}
            {foreach from=$group.Fields item='group_values' name='groupsF'}
                {assign var='total_fields' value=$total_fields+1}
                {if $group_values.value == '' || !$group_values.Details_page || $group_values.Key == 'google_rating' || $group_values.Key == 'total_reviews'}
                    {assign var='value_counter' value=$value_counter+1}
                {/if}
            {/foreach}

            {if !empty($group.Fields) && ($total_fields != $value_counter)}
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
                        {* Skip fields already displayed in custom sections *}
                        {if $item.Key == 'google_rating' || $item.Key == 'total_reviews'}{continue}{/if}

                        {if !empty($item.value) && $item.Details_page}
                            {* Special rendering for boolean fields *}
                            {if $item.Key == 'financing_available' || $item.Key == 'free_consultation'}
                                <div class="solar-bool-field {if $item.value == '1' || $item.value == 'Yes'}solar-bool-yes{else}solar-bool-no{/if}">
                                    <span class="solar-bool-icon">{if $item.value == '1' || $item.value == 'Yes'}&#10003;{else}&#10007;{/if}</span>
                                    <span class="solar-bool-label">{$item.name}</span>
                                </div>
                            {* Special rendering for checkbox fields (services, brands, certs) *}
                            {elseif $item.Key == 'services_offered' || $item.Key == 'panel_brands' || $item.Key == 'certifications'}
                                <div class="solar-tag-field">
                                    <div class="name">{$item.name}</div>
                                    <div class="solar-tags">
                                        {assign var='tag_class' value='solar-tag'}
                                        {if $item.Key == 'certifications'}{assign var='tag_class' value='solar-tag solar-tag-cert'}{/if}
                                        {if $item.Key == 'panel_brands'}{assign var='tag_class' value='solar-tag solar-tag-brand'}{/if}
                                        {$item.value}
                                    </div>
                                </div>
                            {else}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' columnsView=$group.Columns}
                            {/if}
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
