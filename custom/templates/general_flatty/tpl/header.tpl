{include file='head.tpl'}

    <header>
        <section class="point1 d-flex align-items-center flex-wrap pl-3 pr-3 pl-md-0 pr-md-0">
            <div id="top-navigation" class="order-1 flex-fill flex-basis-0 d-flex">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}

                {rlHook name='tplHeaderUserNav'}
            </div>

            <div id="logo" class="mx-auto order-3 order-md-2 text-center mb-2 mb-md-0 flex-fill">
                <a href="{$rlBase}" title="{$config.site_name}" class="solar-brand">
                    <span class="solar-brand-mark" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"></path>
                        </svg>
                    </span>
                    <span class="solar-brand-text">
                        <span class="solar-brand-title">SolarListings</span>
                        <span class="solar-brand-subtitle">Find Solar Installers</span>
                    </span>
                </a>
            </div>

            <div class="top-user-navigation order-2 order-md-3 flex-fill flex-basis-0 d-flex justify-content-end">
                {rlHook name='tplHeaderUserArea'}

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
            </div>
        </section>
        <section class="main-menu">
            <nav class="point1 clearfix">
                <div class="kw-search angel-gradient-light">
                    {strip}
                    <span class="lens"><span></span></span>
                     <span class="field">
                        <form method="post" action="{pageUrl key='search'}">
                            <input type="hidden" name="form" value="keyword_search" />
                            <input placeholder="{$lang.keyword_search}" id="autocomplete" type="text" maxlength="255" name="f[keyword_search]" {if $smarty.post.f.keyword_search}value="{$smarty.post.f.keyword_search}"{/if}/>
                        </form>
                    </span>
                    {/strip}
                    <span class="close"></span>

                    <script>
                        var view_details = '{$lang.view_details}';
                        var join_date = '{$lang.join_date}';
                        var category_phrase = '{$lang.category}';
                    </script>
                </div>

                {include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
            </nav>
        </section>
    </header>
