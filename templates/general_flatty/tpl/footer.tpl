<footer>
    <div class="point1 mx-auto clearfix">
        <div class="top-line position-relative">
            <span class="scroll-top angel-gradient-light"></span>

            <div class="logo">
                <a href="{$rlBase}" title="{$config.site_name}">
                    <img alt="{$config.site_name}" src="{$rlTplBase}img/blank.gif" />
                </a>
            </div>

            <nav class="footer-menu">
                {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
            </nav>
        </div>

        {include file='footer_data.tpl'}
    </div>
</footer>

{if !$isLogin}
    <div id="login_modal_source" class="hide">
        <div class="tmp-dom user-navbar-container">
            {include file='blocks/login_modal.tpl'}
        </div>
    </div>
{/if}

{rlHook name='tplFooter'}

{include file='footerScript.tpl'}
