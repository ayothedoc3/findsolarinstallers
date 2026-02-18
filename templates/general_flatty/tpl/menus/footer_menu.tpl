<!-- footer menu block -->

<ul>
{foreach name='fMenu' from=$footer_menu item='menu_item'}{strip}
    {include file='menus/menu_item.tpl' menuItem=$menu_item itemTag='li'}
{/strip}{/foreach}
</ul>

<!-- footer menu block end -->
