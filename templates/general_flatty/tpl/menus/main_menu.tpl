<!-- main menu block -->

{strip}
<ul class="menu">
    {foreach name='mMenu' from=$main_menu item='menu_item'}
        {include file='menus/menu_item.tpl' menuItem=$menu_item itemTag='li'}
    {/foreach}
    <li class="more hide"><span><span></span><span></span><span></span></span></li>
</ul>
{/strip}

<div class="mobile-menu-button"></div>
<ul id="main_menu_more"></ul>

<!-- main menu block end -->
