<!-- news article -->

<article class="{if $columnClass}{$columnClass}{else}col-md-6{/if} d-flex">
    <div class="mb-{if $boxMode}3{else}5{/if} d-flex flex-fill flex-column">
        <div class="d-flex mb-2">
            <div class="date">{toPrettyDateTime date=$article.Date}</div>
            <div class="ml-auto d-flex align-items-center">
                <span class="mr-2 date">{$article.Views}</span>

                <svg viewBox="0 0 14 10" class="news-article__views grid-icon-fill">
                    <use xlink:href="#icon-eye"></use>
                </svg>
            </div>
        </div>

        {assign var='articlePath' value='path='|cat:$article.Path}
        {if $article.Category_Path}
            {assign var='articlePath' value='category='|cat:$article.Category_Path|cat:'&'|cat:$articlePath}
        {/if}

        <a class="link-large d-flex flex-column" title="{$article.title}" href="{pageUrl key='news' add_url=$articlePath}">
            <h4>{$article.title}</h4>

            <div class="news-article__pic-cont position-relative mt-3 mb-3">
                <img class="position-absolute w-100 h-100" src="{if $article.Picture}{$smarty.const.RL_FILES_URL}news/{$article.Picture}{else}{$rlTplBase}img/blank.gif{/if}" alt="{$article.title}" />
            </div>
            {rlHook name='newsPostCaption'}
        </a>

        {assign var='newsContent' value=$article.content}
        {assign var='newsContent' value=$newsContent|regex_replace:"/(<style[^>]*>[^>]*<\\/style>)/mi":""|strip_tags:false}

        {if !$contentLimit}
            {assign var='contentLimit' value=$config.news_page_content_length}
        {/if}

        {$newsContent|truncate:$contentLimit:'...':false}
        {rlHook name='newsPostContent'}
    </div>
</article>

<!-- news article end -->
