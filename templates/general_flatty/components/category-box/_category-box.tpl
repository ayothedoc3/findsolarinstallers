<!-- category box -->

{assign var='more_categories_link' value=false}
{assign var='type_settings' value=$listing_types.$type}
{assign var='sidebar_mode' value=false}
{assign var='middle_mode' value=false}
{assign var='parent_scrollbar' value=false}

{if $block.Options.categories_style.default == 'grid'}
    {assign var='grid_class' value='col-sm-6 col-md-4 col-lg-3'}
{/if}

{if $typePage}
    {assign var='source_categories' value=$categories}
{else}
    {assign var='source_categories' value=$box_categories.$type}
{/if}

{if $block.Side == 'middle_left' || $block.Side == 'middle_right'}
    {assign var='middle_mode' value=true}
{/if}

{if $block.Side == 'left' || $middle_mode}
    {assign var='sidebar_mode' value=true}
{/if}

{if $sidebar_mode && $bread_crumbs}
    <div class="categories-bc d-md-none d-lg-block">
    {foreach from=$bread_crumbs item='category_bc' name='category_bc'}
        {if $smarty.foreach.category_bc.first || $smarty.foreach.category_bc.last}
            {continue}
        {/if}

        <div class="pb-1">
            <a href="{if $category_bc.category}{categoryUrl category=$category_bc}{else}{pageUrl key=$pageInfo.Key}{/if}" class="d-flex align-items-center">
                <svg viewBox="0 0 16 32" class="categories-box__arrow mr-2 header-usernav-icon-fill">
                   <use xlink:href="#core-icon-arrow-left"></use>
                </svg>
                {$category_bc.name}
            </a>
        </div>
    {/foreach}
    </div>
{/if}

{if $source_categories}
    {assign var='categories_count' value=$source_categories|@count}

    {if $block.Options.visible_categories.default && $categories_count > $block.Options.visible_categories.default}
        {addCSS file=$smarty.const.RL_LIBS_URL|cat:'fancyapps/carousel.css'}
        {assign var='carousel' value=$block.Options.visible_categories.default}
    {/if}

    {assign var='categories_has_subcat' value=false}
    {foreach from=$source_categories item='cat'}
        {if $cat.sub_categories}
            {assign var='categories_has_subcat' value=true}
            {break}
        {/if}
    {/foreach}

    <div class="categories-box mt-2{if $block.Options.display_subcategories.default && !$sidebar_mode} categories-box_subcategory-exists{/if}{if $sidebar_mode} categories-box__style_sidebar{/if}{if $middle_mode} categories-box__style_middle{/if} categories-box__style_{$block.Options.categories_style.default}{if $carousel} carousel{/if}" id="categories_box_{$block.Key}">

        {if $carousel}
        <div class="f-carousel__viewport overflow-hidden">
            <div class="f-carousel__track d-flex">
        {/if}

        <div class="categories-box__slide{if $carousel} f-carousel__slide p-0 w-100 col-12{/if}{if $typePage && isset($category.Parent_ID)} pl-3 pl-md-0 pl-lg-3{/if}">
            <div class="{if !$carousel && $sidebar_mode && $categories_count > 12}{assign var='parent_scrollbar' value=true}categories-box__scrollbar scrollbar{elseif $block.Options.categories_style.default == 'grid'}overflow-hidden{/if}">
                <div{if $block.Options.categories_style.default == 'grid'} class="row"{/if}>
            {foreach from=$source_categories item='cat' name='fCategory'}
                {rlHook name='tplBetweenCategories'}

                <div class="categories-box__item{if !$cat.Count} categories-box__item_empty{/if} {$grid_class}">
                    <div class="categories-box__parent d-flex align-items-center category-wrapper-hook pb-2">
                        {rlHook name='tplPreCategory'}

                        <div class="d-flex align-items-center shrink-fix overflow-hidden{if $block.Options.display_subcategories.default && $categories_has_subcat && !$cat.sub_categories} categories-box__parent-link-wrapper{/if}">
                            {if $cat.sub_categories && $sidebar_mode && $block.Options.display_subcategories.default}
                                <svg viewBox="0 0 32 32" class="categories-box__plus user-select-none mr-2 header-usernav-icon-fill d-md-none d-lg-block">
                                   <use xlink:href="#core-icon-plus"></use>
                                </svg>
                            {/if}

                            {rlHook name='tplPreCategoryName'}

                            <a class="categories-box__parent-link{if $category && $cat.ID == $category.ID} categories-box__parent-link_active{/if}"
                               title="{if $lang[$cat.pTitle]}{$lang[$cat.pTitle]}{else}{$lang[$cat.pName]}{/if}"
                               href="{if isset($cat.Links_type)}{pageUrl key=$cat.Page_key}{else}{categoryUrl category=$cat}{/if}">
                                    {$lang[$cat.pName]}
                            </a>

                            {if $block.Options.display_counter.default}
                                <div class="categories-box__parent-counter ml-2">
                                    <span>{$cat.Count|number_format}</span>
                                </div>
                            {/if}
                        </div>

                        {rlHook name='tplPostCategory'}
                    </div>

                    {if $cat.sub_categories && $block.Options.display_subcategories.default}
                        <div class="categories-box__subcategories pb-3">
                            <div{if $sidebar_mode && !$parent_scrollbar && $cat.sub_categories|@count > 12 && !$block.Options.subcategories_number.default} class="categories-box__scrollbar scrollbar"{/if}>
                            {foreach from=$cat.sub_categories item='sub_cat' name='subCatF'}
                                <div class="categories-box__subcategory align-items-center {strip}
                                    {if $block.Options.subcategories_number.default && $smarty.foreach.subCatF.iteration > $block.Options.subcategories_number.default} categories-box__hidden d-none{else}d-flex{/if}
                                    {/strip}">
                                    {rlHook name='tplPreSubCategory'}

                                    <a class="categories-box__subcategory-link" title="{if $lang[$sub_cat.pTitle]}{$lang[$sub_cat.pTitle]}{else}{$sub_cat.name}{/if}"
                                       href="{categoryUrl category=$sub_cat type=$cat.Type}">
                                        {$sub_cat.name}
                                    </a>

                                    {* @since 4.9.3 *}
                                    {rlHook name='tplPostSubCategory'}
                                </div>
                            {/foreach}
                            </div>

                            {if $block.Options.subcategories_number.default && $smarty.foreach.subCatF.total > $block.Options.subcategories_number.default}
                                {if $block.Options.more_subcategories_style.default == 'more_subcategories_popup'}
                                    <span class="categories-box__more-subcategories" title="{$lang.show_other_categories}">{$lang.more} &raquo;</span>
                                    {assign var='more_categories_link' value=true}
                                {else}
                                    <a href="{if isset($cat.Links_type)}{pageUrl key=$cat.Page_key}{else}{categoryUrl category=$cat}{/if}"
                                       class="button low mt-2"
                                       title="{$lang.show_other_categories}">
                                        {$lang.show_other_categories}
                                    </a>
                                {/if}
                            {/if}
                        </div>
                    {/if}
                </div>

        {if $carousel && $smarty.foreach.fCategory.iteration%$carousel == 0 && !$smarty.foreach.fCategory.last}
                </div>
            </div>
        </div>
        <div class="categories-box__slide f-carousel__slide p-0 w-100 col-12">
            <div>
                <div class="{if $block.Options.categories_style.default == 'grid'}row{/if}">
        {/if}
            {/foreach}
                </div>
            </div>
        </div>

        {if $carousel}
                </div>
            </div>
        {/if}
    </div>

    {if $carousel}
        <script class="fl-js-dynamic">
        var category_box_id = '#categories_box_{$block.Key}';
        {literal}
        flUtil.loadScript(rlConfig['libs_url']+'fancyapps/carousel.umd.js', function(){
            new Carousel($(category_box_id).get(0), {
                Navigation: false,
                Autoplay: false
            });
        });
        {/literal}
        </script>
    {/if}
{else}
    {if !isset($search_results)}
        {$lang.listing_type_no_categories}
    {/if}
{/if}

{if $more_categories_link || $sidebar_mode}
    {addJS file=$rlTplBase|cat:'components/category-box/_category-box.js'}
{/if}

<!-- category box end -->
