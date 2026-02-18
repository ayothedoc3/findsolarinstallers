<!-- news block tpl -->

{if !empty($all_news)}
    <svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        {include file='../img/svg/view.svg'}
    </svg>

    {assign var='column_class' value='col-md-4'}

    {if $block.Side == 'middle_left' || $block.Side == 'middle_right'}
        {assign var='column_class' value='col-lg-12'}
    {elseif $block.Side == 'left'}
        {assign var='column_class' value='col-md-4 col-lg-12'}
    {/if}

    <div class="row">
    {foreach from=$all_news item='news_item'}
        {include file=$controllerDir|cat:'news/article.tpl'
                 article=$news_item
                 columnClass=$column_class
                 contentLimit=$config.news_block_content_length
                 boxMode=true}
    {/foreach}
    </div>

    <div class="text-center">
        <a title="{$lang.all_news}" class="button w-100 all-news-button" href="{pageUrl key='news'}">{$lang.all_news}</a>
    </div>
{else}
    {$lang.no_news}
{/if}

<!-- news block tpl end -->
