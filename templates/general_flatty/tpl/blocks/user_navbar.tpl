<!-- user navigation bar -->

{if $pages.login}
    <span class="circle priority-z-index{if $languages|@count <= 1} stick{/if}" id="user-navbar">
        {if $new_messages}<span class="notify"></span>{/if}
        <span class="default"><span {if $isLogin}class="logged-in"{/if}></span></span>
        <span class="content {if $isLogin}a-menu{/if} hide">
            {if $isLogin}
                {include file='menus/user_navbar_menu.tpl'}
            {else}
                <span class="user-navbar-container">
                    {include file='blocks/login_modal.tpl'}
                </span>
            {/if}
        </span>
    </span>
{/if}

<!-- user navigation bar end -->
