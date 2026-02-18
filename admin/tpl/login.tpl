<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$smarty.const.RL_LANG_CODE|lower}">
<head>

<title>{$lang.login_to} {$lang.rl_admin_panel}</title>

<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1" />
<meta name="generator" content="reefless_admin" />
<meta http-equiv="Content-Type" content="text/html; charset={$config.encoding}" />
<link href="{$rlTplBase}css/login.css?rev={$config.static_files_revision}" type="text/css" rel="stylesheet" />
<link href="{$rlTplBase}css/style.css?rev={$config.static_files_revision}" type="text/css" rel="stylesheet" />
<link rel="shortcut icon" href="{$rlTplBase}img/favicon.ico?rev={$config.static_files_revision}" />
<link href="{$rlTplBase}css/ext/ext-all.css?rev={$config.static_files_revision}" type="text/css" rel="stylesheet" />
<link href="{$rlTplBase}css/ext/rlExt.css?rev={$config.static_files_revision}" type="text/css" rel="stylesheet" />

{$ajaxJavascripts}

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.js?rev={$config.static_files_revision}"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}extJs/ext-base.js?rev={$config.static_files_revision}"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}extJs/ext-all.js?rev={$config.static_files_revision}"></script>
<script type="text/javascript" src="{$rlBase}js/login.js?rev={$config.static_files_revision}"></script>

</head>
<body>
<div id="height">
    <div id="login_block">
        <div class="wrapper">
            <img class="logo" src="{$rlTplBase}img/logo.png" />
            {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_admin_module}
                <div class="error">
                    <div class="inner">
                        <div class="icon"></div>
                        {assign var='periodVar' value=`$smarty.ldelim`period`$smarty.rdelim`}
                        {assign var='replace' value='<b>'|cat:$config.security_login_attempt_admin_period|cat:'</b>'}
                        {$lang.login_attempt_error|replace:$periodVar:$replace}
                    </div>
                </div>
            {else}
                <form name="login" action="" method="post" onsubmit="return false;">
                    <div class="relative" style="margin: 0 0 5px">
                        <input class="w-100" maxlength="64" type="text" id="username" name="username" placeholder="{$lang.username}" />
                    </div>

                    <div class="relative" style="margin: 0 0 5px">
                        <input class="w-100" maxlength="64" type="password" id="password" name="password" placeholder="{$lang.password}" />
                    </div>

                    <select class="w-100{if $langCount < 2} disabled{/if}" title="{$lang.rl_interface}" id="interface" {if $langCount < 2}disabled="disabled"{/if}>
                        {foreach from=$languages item='languages' name='lang_foreach'}
                            <option value="{$languages.Code}" {if $smarty.const.RL_LANG_CODE == $languages.Code} selected="selected"{/if}>{$languages.name}</option>
                        {/foreach}
                    </select>

                    <div style="margin-top: 20px;">
                        <input class="w-100" id="login_button" type="submit" name="go" value="{$lang.login}" />
                    </div>
                </form>
            {/if}
        </div>
    </div>
</div>

<!-- copyrights -->
<div id="login_footer">
    &copy; <a href="{$lang.flynax_url}">{$lang.copy_rights}</a> {$lang.version} <b>{$config.rl_version}</b>
</div>
<!-- copyrights end -->

<script type="text/javascript">
var lang = new Array();

lang['loading'] = '{$lang.loading|escape}';
lang['alert'] = '{$lang.alert|escape}';

{if isset($smarty.get.session_expired) || $smarty.get.action == 'session_expired'}
    fail_alert('', '{$lang.session_expired}');
{/if}

{literal}

var is_visible = true;

$(document).ready(function(){
    $('#login_button').click(function(){
        jsLogin(
            "{/literal}{$lang.rl_empty_username|regex_replace:'/[\r\t\n]/':'<br />'}{literal}",
            "{/literal}{$lang.rl_empty_pass|regex_replace:'/[\r\t\n]/':'<br />'}{literal}"
        );
    });
});

{/literal}
</script>

{rlHook name='apTplLogin'}

</body>
</html>
