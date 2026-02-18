<!-- news tpl -->

<svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    {include file='../img/svg/view.svg'}
</svg>

<div class="content-padding">
    {if $article}
        {if $article.Picture}
        <div class="row">
            <div class="col-md-4">
                <img class="w-100" src="{$smarty.const.RL_FILES_URL}news/{$article.Picture}" alt="{$article.title}" />

                <div class="d-flex mt-2">
                    {if $article.Category_Name}
                        <div class="date">{$lang.category}:
                            <a href="{pageUrl key='news' add_url='category='|cat:$article.Category_Path}" title="{$article.Category_Name}">
                                {$article.Category_Name}
                            </a>
                        </div>
                    {/if}
                    <div class="ml-auto d-flex align-items-center">
                        <span class="mr-2 date">{$article.Views}</span>

                        <svg viewBox="0 0 14 10" class="news-article__views grid-icon-fill">
                            <use xlink:href="#icon-eye"></use>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
        {/if}

        <div class="d-flex mb-2">
            <div class="date">{toPrettyDateTime date=$article.Date}</div>
            {if !$article.Picture}
            <div class="ml-auto d-flex align-items-center">
                <span class="mr-2 date">{$article.Views}</span>

                <svg viewBox="0 0 14 10" class="news-article__views grid-icon-fill">
                    <use xlink:href="#icon-eye"></use>
                </svg>
            </div>
            {/if}
        </div>

        {rlHook name='newsPostCaption'}

        <article class="news-article__content">
            {$article.content}
            {rlHook name='newsPostContent'}
        </article>

        <div class="mt-2">
            <a title="{$lang.back_to_news}" href="{$back_link}">{$lang.back_to_news}</a>
        </div>

        {if $article.Picture}
            </div>
        </div>
        {/if}
    {else}
        {if $news}
            <div class="row">
            {foreach from=$news item='news_item'}
                {include file=$controllerDir|cat:'news/article.tpl' article=$news_item}
            {/foreach}
            </div>

            <!-- paging block -->
            {if $newsCurrentCategory && $newsCurrentCategory.Path}
                {assign var='newsCategoryPath' value=$newsCurrentCategory.Path}

                {if !$config.mod_rewrite}
                    {assign var='newsCategoryPath' value='category='|cat:$newsCategoryPath}
                {/if}
            {/if}

            {paging calc=$pageInfo.calc total=$news current=$pageInfo.current per_page=$config.news_at_page url=$newsCategoryPath}
            <!-- paging block end -->
        {else}
            <div class="text-notice">{$lang.no_news}</div>
        {/if}
    {/if}

    {rlHook name='newsBottomTpl'}
</div>

<!-- news tpl end -->
