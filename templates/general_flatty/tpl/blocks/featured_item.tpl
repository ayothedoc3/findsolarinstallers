{php}
global $page_info;

$block = $this -> get_template_vars('block');
$side_bar_exists = $this -> get_template_vars('side_bar_exists');
$class = 'col-sm-6 col-md-4 ';
$class .= $side_bar_exists ? 'col-lg-4' : 'col-lg-3';

if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
    $class = 'col-sm-6';
}

$this -> assign('box_item_class', $class);
{/php}

{assign var='price' value=$featured_listing.fields[$config.price_tag_field]}

{rlHook name='featuredItemTop'}

<li {if $featured_listing.ID}id="fli_{$featured_listing.ID}"{/if} class="{$box_item_class}{if !$featured_listing.Main_photo} no-picture{/if}">
    {if $listing_types.$type.Photo}
        <div class="picture">
            <a title="{$featured_listing.listing_title}" {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
                <img src="{if $featured_listing.Main_photo}{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                    {if $featured_listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo_x2} 2x"{/if}
                    alt="{$featured_listing.listing_title}"
                    loading="lazy" />
                {rlHook name='tplFeaturedItemPhoto'}
            </a>
        </div>
    {/if}

    <ul class="card-info">
        <li class="title" title="{$featured_listing.fields.title.value|strip_tags}">
            <a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
                {$featured_listing.listing_title}
            </a>
        </li>

        {if $featured_listing.fields.bedrooms.value || $featured_listing.fields.bathrooms.value || $featured_listing.fields.square_feet.value}
            <li class="services">{strip}
                {if $featured_listing.fields.bedrooms.value}
                    <span title="{$featured_listing.fields.bedrooms.name}" class="badrooms">{$featured_listing.fields.bedrooms.value}</span>
                {/if}
                {if $featured_listing.fields.bathrooms.value}
                    <span title="{$featured_listing.fields.bathrooms.name}" class="bathrooms">{$featured_listing.fields.bathrooms.value}</span>
                {/if}
                {if $featured_listing.fields.square_feet.value}
                    <span title="{$featured_listing.fields.square_feet.name}" class="square_feet">{$featured_listing.fields.square_feet.value}</span>
                {/if}
            {/strip}</li>
        {/if}

        <li class="fields">
            {foreach from=$featured_listing.fields item='item' key='field' name='fieldsF'}
                {if empty($item.value) || !$item.Details_page || ($item.Key == $config.price_tag_field || $item.Key|in_array:$tpl_settings.listing_grid_except_fields)}{continue}{/if}

                <span id="flf_{$featured_listing.ID}_{$item.Key}">{$item.value}</span>
            {/foreach}
        </li>

        <li class="two-inline price_tag">
            <nav class="icons">
                {rlHook name='tplFeaturedItemIcon'}

                <span id="fav_{$featured_listing.ID}" class="favorite add" title="{$lang.add_to_favorites}">
                    <span class="icon"></span>
                </span>
            </nav>

            {if $price.value}
                <div>
                    {if $price.Options.from}{$lang.price_from}{/if}
                    <span>
                        {if $price.Options.option && $price.Options.option == $price.Contact_form_option}
                            <a href="javascript://"
                                class="contact-owner price-contact-form"
                                data-listing-id="{$featured_listing.ID}"
                                data-account-id="{$featured_listing.Account_ID}"
                            >
                        {/if}
                        {$price.value}
                        {if $price.Options.option && $price.Options.option == $price.Contact_form_option}</a>{/if}
                    </span>
                    {if $featured_listing.sale_rent == 2 && $featured_listing.fields.time_frame.value}
                        &nbsp;/ {$featured_listing.fields.time_frame.value}
                    {/if}
                </div>
            {/if}
        </li>

        {rlHook name='tplFeaturedItemAdInfo'}
    </ul>
</li>

{rlHook name='featuredItemBottom'}
