<!-- listing item -->

{rlHook name='listingTop'}

{if $listing.Listing_type}
    {assign var='listing_type' value=$listing_types[$listing.Listing_type]}
{/if}

{if $listing_type.Photo || $pageInfo.Controller|in_array:$_mixedListingControllers || $smarty.request.mode == 'rvLoadListings'}
    {assign var='pictures_available' value=true}
{else}
    {assign var='pictures_available' value=false}
{/if}

{assign var='price' value=$listing.fields[$config.price_tag_field]}

<article class="item {if $listing.Featured} featured{/if} col-sm-4{if !$side_bar_exists} col-md-3{/if} {rlHook name='tplListingItemClass'}">
    <div class="main-column clearfix">
        {if $pictures_available}
            <a title="{$listing.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">
                <div class="picture{if !$listing.Main_photo} no-picture{/if}">
                    <img src="{if $listing.Main_photo}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                        {if $listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$listing.Main_photo_x2} 2x"{/if}
                        alt="{$listing.listing_title}"
                        loading="lazy" />
                    {rlHook name='tplListingItemPhoto'}
                    {if $listing.Featured}<div class="label"><div title="{$lang.featured}">{$lang.featured}</div></div>{/if}
                    {if !empty($listing.Main_photo) && $config.grid_photos_count}
                        <span accesskey="{$listing.Photos_count}"></span>
                    {/if}
                </div>
            </a>
        {/if}

        <div class="data-container">
            <div class="two-inline">
                <div>{rlHook name='listingAfterPrice'} {* @todo - remove after new version of Shopping Cart plugin is available *}</div>
                {if $price.value}
                    <div class="price-tag">
                        {if $price.Options.from}{$lang.price_from}{/if}
                        <span>
                            {if $price.Options.option && $price.Options.option == $price.Contact_form_option}
                                <a href="javascript://"
                                    class="contact-owner price-contact-form"
                                    data-listing-id="{$listing.ID}"
                                    data-account-id="{$listing.Account_ID}"
                                >
                            {/if}
                            {$price.value}
                            {if $price.Options.option && $price.Options.option == $price.Contact_form_option}</a>{/if}
                        </span>
                        {if $listing.sale_rent == 2 && $listing.fields.time_frame.value}
                            / {$listing.fields.time_frame.value}
                        {/if}
                    </div>
                {/if}
            </div>

            <ul class="card-info{if $config.sf_display_fields} with-names{/if}">
                <li class="title">
                    <a class="link-large"
                        title="{$listing.listing_title}"
                        {if $config.view_details_new_window}target="_blank"{/if}
                        href="{$listing.url}">
                        {$listing.listing_title}
                    </a>
                </li>

                {if $listing.fields.bedrooms.value || $listing.fields.bathrooms.value || $listing.fields.square_feet.value}
                        <li class="services">{strip}
                            {if $listing.fields.bedrooms.value}
                                <span title="{$listing.fields.bedrooms.name}" class="badrooms">{$listing.fields.bedrooms.value}</span>
                            {/if}
                            {if $listing.fields.bathrooms.value}
                                <span title="{$listing.fields.bathrooms.name}" class="bathrooms">{$listing.fields.bathrooms.value}</span>
                            {/if}
                            {if $listing.fields.square_feet.value}
                                <span title="{$listing.fields.square_feet.name}" class="square_feet">{$listing.fields.square_feet.value}</span>
                            {/if}
                        {/strip}</li>
                        {/if}

                <li class="fields">{strip}
                    {foreach from=$listing.fields item='item' key='field' name='fListings'}
                    {if empty($item.value) || !$item.Details_page || ($item.Key == $config.price_tag_field || $item.Key|in_array:$tpl_settings.listing_grid_except_fields)}{continue}{/if}

                        {if $config.sf_display_fields}
                            <div class="table-cell small clearfix">
                                <div class="name">{$item.name}</div>
                                <div class="value">{$item.value}</div>
                            </div>
                        {else}
                            <span>{$item.value}</span>
                        {/if}
                    {/foreach}

                    {rlHook name='listingAfterFields'}
                {/strip}</li>
                <li class="middle d-flex">
                    {rlHook name='listingBeforeStats'}

                    <ul class="nav-column{if !$price.value} stick-top{/if}">
                        <li id="fav_{$listing.ID}" class="favorite add" title="{$lang.add_to_favorites}"><span class="icon"></span><span class="link">{$lang.add_to_favorites}</span></li>
                        {rlHook name='listingNavIcons'}
                    </ul>

                    <div class="stat-line ml-3 ml-md-auto d-flex align-items-center">
                        {rlHook name='listingAfterStats'}

                        {if $config.display_posted_date}<span title="{$lang.posted_date}" class="hint date">{$listing.Date|date_format:$smarty.const.RL_DATE_FORMAT}</span>{/if}
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- listing item end -->
