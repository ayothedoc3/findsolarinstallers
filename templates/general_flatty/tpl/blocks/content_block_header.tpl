{if $block.Options && $block.Options.header_link.default && $block.Header}
    {assign var='header_link' value=true}
{/if}

<section class="content_block{if !$block.Tpl} no-style{/if}{if isset($block.Header) && !$block.Header} no-header{/if}{if $block.Key|strpos:'ltcb_' === 0}{if ','|explode:$types|@count <= 1} categories-box-nav{/if}{/if}{if $block_class} {$block_class}{/if}{if $header_link} has-header-link d-flex flex-wrap align-items-center{/if}">
    {if $block.Header}<h3>{if $name}{$name}{else}{$block.name}{/if}</h3>{/if}
    {if $header_link}
        <a class="order-3 order-md-1 m-auto{if $tpl_settings.block_stick_header_link} ml-md-3{else} mr-md-0 ml-md-auto{/if}"
           href="{if $block.Options.header_link_page_link.default}{$block.Options.header_link_page_link.default}{else}{pageUrl key=$block.Options.header_link_page_key.default}{/if}">{phrase key='blocks+header_link+'|cat:$block.Key}</a>
    {/if}
    <div{if $header_link} class="flex-fill w-100 order-2 mb-3 mb-md-0"{/if}>
