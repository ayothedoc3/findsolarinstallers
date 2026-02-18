<!-- more news block tpl -->

{if $otherArticles}
    <div class="row">
        {foreach from=$otherArticles item='otherArticle'}
            {include file=$controllerDir|cat:'news/article.tpl'
                article=$otherArticle
                columnClass='col-md-4'
                contentLimit=$config.news_block_content_length
            }
        {/foreach}
    </div>
{else}
    <div class="text-notice">{$lang.no_news}</div>
{/if}

<!-- more news block tpl end -->
