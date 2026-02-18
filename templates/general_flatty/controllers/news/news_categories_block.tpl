<!-- news categories block tpl -->

{if !empty($newsCategories)}
    {assign var='sidebar_mode' value=false}
    {assign var='categories_count' value=$newsCategories|@count}

    {if $block.Side == 'left'}
        {assign var='sidebar_mode' value=true}
    {/if}

    {if $newsCurrentCategory}
    <div class="pb-1">
        <a href="{pageUrl key='news'}" class="d-flex align-items-center">
            <svg viewBox="0 0 16 32" class="categories-box__arrow mr-2 header-usernav-icon-fill">
               <use xlink:href="#core-icon-arrow-left"></use>
            </svg>
            {$lang.all_news}
        </a>
    </div>
    {/if}

    <div class="categories-box mt-2{if $sidebar_mode} categories-box__style_sidebar{/if} categories-box__style_grid">
        <div class="categories-box__slide{if $newsCurrentCategory} pl-3 pl-md-0 pl-lg-3{/if}">
            <div class="{if $sidebar_mode && $categories_count > 12} categories-box__scrollbar scrollbar{/if}">
                <div class="row">
                {foreach from=$newsCategories item='newsCategory'}
                    <div class="categories-box__item col-md-4 col-lg-3">
                        <div class="categories-box__parent d-flex align-items-center {if $sidebar_mode}pb-1 pb-md-2 pb-lg-1{else}pb-2{/if}">
                            <div class="d-flex align-items-center shrink-fix overflow-hidden">
                                <a class="categories-box__parent-link{if $newsCurrentCategory && $newsCurrentCategory.ID == $newsCategory.ID} categories-box__parent-link_active{/if}"
                                   title="{$newsCategory.Name}"
                                   href="{pageUrl key='news' add_url='category='|cat:$newsCategory.Path}">
                                        {$newsCategory.Name}
                                </a>
                            </div>
                        </div>
                    </div>
                {/foreach}
                </div>
            </div>
        </div>
    </div>
{else}
    {$lang.listing_type_no_categories}
{/if}

<!-- news categories block tpl end -->
