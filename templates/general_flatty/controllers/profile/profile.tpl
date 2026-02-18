<!-- my profile -->

{if $step == 'purchase'}
    {if $membership_plan.Subscription}
        <span class="text-notice" style="display: inline-block;margin-bottom: 15px;">{$lang.notice_has_active_membership_subscription}</span>

        <div class="content-padding">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='subscription_details' name=$lang.subscription_details tall=true}

            <div class="table-cell">
                <div class="name">{$lang.item}:</div>
                <div class="value">{$membership_plan.name}</div>
            </div>
            <div class="table-cell">
                <div class="name">{$lang.price}:</div>
                <div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$membership_plan.Price}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
            </div>
            <div class="table-cell">
                <div class="name">{$lang.subscription_period}:</div>
                {assign var='subscription_period_name' value='subscription_period_'|cat:$membership_plan.Period}
                <div class="value">{$lang.$subscription_period_name}</div>
            </div>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

            <div class="table-cell">
                <div class="value">
                    <a class="unsubscription button" id="unsubscription-{$account_info.ID}" href="javascript:void(0);" accesskey="{$account_info.ID}-{$membership_plan.Subscription_ID}-{$membership_plan.Subscription_service}">{$lang.unsubscription}</a>
                </div>
            </div>
        </div>

        <script class="fl-js-dynamic">
        {literal}

        $(document).ready(function(){
            $('.unsubscription').each(function() {
                $(this).flModal({
                    caption: '',
                    content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
                    prompt: 'flSubscription.cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +', \'{/literal}{$pageInfo.Key}{literal}\')',
                    width: 'auto',
                    height: 'auto'
                });
            });
        });

        {/literal}
        </script>
    {else}
        <form name="account_reg_form" method="post" action="{pageUrl key='my_profile' add_url='step=purchase'}" enctype="multipart/form-data">
            <input type="hidden" name="form" value="plan" />
            <input type="hidden" name="step" value="purchase" />

            {if $plans}
                <div class="plans-container membership-plans">
                    {assign var=subscription_exists value=false}
                    {foreach from=$plans item='plan'}{if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}{assign var=subscription_exists value=true}{break}{/if}{/foreach}

                    <ul class="plans{if $plans|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}">
                        {foreach from=$plans item='plan' name='plansF'}{strip}
                            {if $plan.ID != $account_info.plan.ID}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'membership_plan.tpl'}
                            {/if}
                        {/strip}{/foreach}
                    </ul>
                </div>

                <script class="fl-js-dynamic">
                var plans = Array();
                var selected_plan_id = 0;
                {foreach from=$plans item='plan'}
                    plans[{$plan.ID}] = new Array();
                    plans[{$plan.ID}]['Key'] = '{$plan.Key}';
                    plans[{$plan.ID}]['Featured'] = {$plan.Featured|@intval};
                {/foreach}

                {literal}

                $(document).ready(function(){
                    flynax.planClick();
                    flynax.qtip();
                });

                {/literal}
                </script>

                <span class="form-buttons">
                    <input type="submit" value="{$lang.continue}" />
                    <a class="red margin close" href="{pageUrl key=$pageInfo.Key vars='info=membership'}">
                        {$lang.cancel}
                    </a>
                </span>
            {else}
                <span class="text-notice">{$lang.notice_no_membership_plans_related}</span>
            {/if}
        </form>
    {/if}
{else}
    {addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/jquery.qtip.js'}
    {addJS file=$smarty.const.RL_LIBS_URL|cat:'ckeditor/ckeditor.js'}

    {if $config.membership_module}
        {addCSS file=$rlTplBase|cat:'controllers/profile/packages.css'}
    {/if}

    <script class="fl-js-dynamic">flynax.qtip(); flynax.phoneField();</script>

    <!-- tabs -->
    {if $tabs|@count > 2}
    <ul class="tabs tabs-hash">
        {foreach from=$tabs item='tab' name='tabF'}{strip}
            {if $tab.key == 'password'}{continue}{/if}
            <li {if (!isset($smarty.request.info) && $smarty.foreach.tabF.first) || $smarty.request.info == $tab.key}class="active"{/if} id="tab_{$tab.key}">
                <a href="#{$tab.key}" data-target="{$tab.key}">{$tab.name}</a>
            </li>
        {/strip}{/foreach}
    </ul>
    {/if}
    <!-- tabs end -->

    <div class="content-padding">
        <!-- profile -->
        <div id="area_profile" class="tab_area {if !isset($smarty.request.info) || $smarty.request.info == 'profile'}{else}hide{/if}">
            <form style="margin-bottom: 30px;" method="post" action="{pageUrl key=$pageInfo.Key}" enctype="multipart/form-data">
                <input type="hidden" name="info" value="profile" />
                <input type="hidden" name="fromPost_profile" value="1" />

                <div class="submit-cell">
                    <div class="name">{$lang.mail}</div>
                    <div class="field single-field">
                        <input type="text" name="profile[mail]" maxlength="150" {if $smarty.post.profile.mail}value="{$smarty.post.profile.mail}"{/if} />
                        {if $config.account_edit_email_confirmation}
                            <div id="email_change_notice" class="notice_message {if !$aInfo.Mail_tmp}hide{/if}">
                                {if $aInfo.Mail_tmp}
                                    {$lang.account_edit_email_confirmation_info|replace:'[e-mail]':$aInfo.Mail_tmp}
                                {else}
                                    <b>{$lang.notice}</b>: {$lang.account_edit_email_confirmation_notice}
                                    <script class="fl-js-dynamic">
                                    {literal}

                                    $(document).ready(function(){
                                        $('input[name="profile[mail]"]').focus(function(){
                                            $('#email_change_notice').fadeIn('slow');
                                        });
                                    });

                                    {/literal}
                                    </script>
                                {/if}
                            </div>
                        {/if}

                        <label style="padding: 15px 0 5px;display: block;">
                            <input value="1" type="checkbox" {if $smarty.post.profile.display_email}checked="checked"{/if} name="profile[display_email]" /> {$lang.display_email}
                        </label>

                        {rlHook name='tplProfileCheckbox'} <!-- > 4.3.0 -->
                    </div>
                </div>

                {if $account_info.Own_page}
                <div class="submit-cell">
                    <div class="name">{$lang.personal_address}</div>
                    <div class="field {if $profile_info.Own_address}checkbox-field{/if}">
                        {if $profile_info.Own_location}
                            {if $config.account_wildcard}
                                {$scheme}://<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />.{$domain}
                            {else}
                                {$scheme}://{$domain}/<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />
                            {/if}
                            <div class="notice_message">{$lang.latin_characters_only}</div>
                        {else}
                            <a target="_blank" href="{$profile_info.Personal_address}">
                                {$profile_info.Personal_address}
                            </a>
                        {/if}
                    </div>
                </div>
                {/if}

                {if $languages|@count > 1}
                <div class="submit-cell">
                    <div class="name">{$lang.profile_lang}</div>
                    <div class="field single-field">
                        <select name="profile[lang]">
                            {foreach from=$languages item="lang_item"}
                                <option value="{$lang_item.Code}" {if $profile_info.Lang == $lang_item.Code}selected="selected"{/if}>{$lang_item.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {/if}

                <div class="submit-cell">
                    <div class="name"></div>
                    <div class="field">
                        {include file='blocks/agreement_fields.tpl' selected_atype=$profile_info.Type}
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name"></div>
                    <div class="field"><input type="submit" value="{$lang.save}" id="profile_submit" /></div>
                </div>
            </form>

            <!-- manage password -->
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='change_password_area' name=$lang.manage_password}

            <div class="submit-cell">
                <div class="name">{$lang.current_password}</div>
                <div class="field single-field"><input class="wauto" type="password" id="current_password" size="25" maxlength="30" /></div>
            </div>

            <div class="submit-cell">
                <div class="name">{$lang.new_password}</div>
                <div class="field single-field two-inline left">
                    <div><input id="new_password" size="25" class="wauto" type="password" name="profile[password]" maxlength="50" {if $smarty.post.profile.password}value="{$smarty.post.profile.password}"{/if} /></div>
                    {if $config.account_password_strength}
                        <div>
                            <input type="hidden" id="password_strength" value="" />
                            <div class="password_strength">
                                <div class="scale">
                                    <div class="color"></div>
                                    <div class="shine"></div>
                                </div>
                                <div id="pass_strength"></div>
                            </div>

                            <script class="fl-js-dynamic">
                            {literal}

                            $(document).ready(function(){
                                flynax.passwordStrength();

                                $('#new_password').blur(function(){
                                    if ( rlConfig['account_password_strength'] ) {
                                        if ( $('#password_strength').val() < 3 ) {
                                            printMessage('warning', lang['password_weak_warning'])
                                        }
                                        else {
                                            $('div.warning div.close').trigger('click');
                                        }
                                    }
                                });
                            });

                            {/literal}
                            </script>
                        </div>
                    {/if}
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">{$lang.new_password_repeat}</div>
                <div class="field single-field"><input class="wauto" size="25" type="password" id="password_repeat" maxlength="30" /></div>
            </div>

            <div class="submit-cell buttons">
                <div class="name"></div>
                <div class="field"><input id="change_password" type="button" value="{$lang.change}" /></div>
            </div>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

            <script class="fl-js-dynamic">
            {literal}

            $(document).ready(function(){
                $('#change_password').click(function(){
                    xajax_changePass( $('#current_password').val(), $('#new_password').val(), $('#password_repeat').val() );
                    $(this).val('{/literal}{$lang.loading}{literal}');
                });
            });

            {/literal}
            </script>
            <!-- manage password end -->
        </div>
        <!-- profile end -->

        <!-- account -->
        {if !empty($profile_info.Fields)}
            <div id="area_account" class="tab_area {if $smarty.request.info != 'account'}hide{/if}">
                <form method="post" action="{pageUrl key=$pageInfo.Key vars='info=account'}" enctype="multipart/form-data">
                    <input type="hidden" name="info" value="account" />
                    <input type="hidden" name="fromPost_account" value="1" />

                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'reg_account.tpl' fields=$profile_info.Fields}

                    <div class="submit-cell">
                        <div class="name"></div>
                        <div class="field"><input type="submit" name="finish" value="{$lang.edit}" /></div>
                    </div>
                </form>
            </div>
        {/if}
        <!-- account end -->

        <!-- membership -->
        {if $config.membership_module}
            <div id="area_membership" class="tab_area {if $smarty.request.info != 'membership'}hide{/if}">
                {if $membership_plan}
                    <ul class="packages packages_single">
                        <li class="d-flex flex-column flex-md-row">
                            <div class="package__frame"
                                {if $membership_plan.Color}
                                style="background-color: #{$membership_plan.Color};border-color: #{$membership_plan.Color};"
                                {/if}
                            >
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <h3 class="package__name mb-2 mb-md-3">{$membership_plan.name}</h3>
                                    {if $ms_plans_total > 0}
                                        <a href="{pageUrl key='my_profile' add_url='step=purchase'}" class="button low mb-2 mb-md-3">{$lang.change_plan}</a>
                                    {/if}
                                </div>
                                <div class="d-flex flex-column flex-md-row">
                                    <div class="mr-md-4">
                                        <div class="package__price mb-3">
                                            {if $membership_plan.Price > 0}
                                                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                                {$membership_plan.Price}
                                                {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
                                            {else}
                                                {$lang.free}
                                            {/if}
                                        </div>

                                        <div class="table-cell small">
                                            {if $account_info.Status == 'active' && $account_info.Payment_status == 'paid'}
                                                <div class="name w-auto">{$lang.active_till}</div>
                                                <div class="value">
                                                    {if $membership_plan.Plan_period}
                                                        {$membership_plan.Plan_expire|date_format:$smarty.const.RL_DATE_FORMAT}
                                                    {else}
                                                        {$lang.unlimited_short}
                                                    {/if}
                                                </div>
                                            {else}
                                                <div class="name w-auto">{$lang.status}</div>
                                                <div class="value overdue">
                                                    {if $account_info.Payment_status == 'unpaid'}
                                                        {if $account_info.plan.Price}
                                                            {$lang.not_paid}
                                                        {else}
                                                            {$lang.expired}
                                                        {/if}
                                                    {else}
                                                        {$lang[$account_info.Status]}
                                                    {/if}
                                                </div>
                                            {/if}
                                        </div>
                                    </div>

                                    <div class="package__listing-info">
                                        {if $membership_plan.Services}
                                            {foreach from=$membership_services item='service'}
                                                {if $membership_plan.Services[$service.Key]}
                                                    {assign var='serviceValue' value=$lang.yes}
                                                {else}
                                                    {assign var='serviceValue' value=$lang.no}
                                                {/if}
                                                {include file=$controllerDir|cat:'profile/profile_service.tpl' serviceName=$service.name serviceValue=$serviceValue}
                                            {/foreach}
                                        {/if}
                                    </div>
                                </div>
                            </div>

                            <div class="package__stats align-content-center flex-shrink-0 ml-md-3 ml-xl-5">
                                {if isset($membership_plan.Services.add_listing)}
                                    {if $membership_plan.Advanced_mode}
                                        <div class="table-cell clearfix">
                                            <div class="name">{$lang.standard_stat_label}:</div>
                                            <div class="value{if empty($membership_plan.Standard_remains) && !empty($membership_plan.Standard_listings)} overdue{/if}">
                                                {if empty($membership_plan.Standard_listings)}
                                                    <b>{$lang.unlimited_short}</b>
                                                {else}
                                                    {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                                                    {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                                                    {$lang.rest_of_amount|replace:$rRest:$membership_plan.Standard_remains|replace:$rAmount:$membership_plan.Standard_listings}
                                                {/if}
                                            </div>
                                        </div>
                                        <div class="table-cell clearfix">
                                            <div class="name">{$lang.featured_stat_label}:</div>
                                            <div class="value{if empty($membership_plan.Featured_remains) && !empty($membership_plan.Featured_listings)} overdue{/if}">
                                                {if empty($membership_plan.Featured_listings)}
                                                    <b>{$lang.unlimited_short}</b>
                                                {else}
                                                    {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                                                    {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                                                    {$lang.rest_of_amount|replace:$rRest:$membership_plan.Featured_remains|replace:$rAmount:$membership_plan.Featured_listings}
                                                {/if}
                                            </div>
                                        </div>
                                    {else}
                                        <div class="table-cell clearfix">
                                            <div class="name">
                                                {if $membership_plan.Featured_listing}
                                                    {$lang.featured_stat_label}:
                                                {else}
                                                    {$lang.standard_stat_label}:
                                                {/if}
                                            </div>
                                            <div class="value{if empty($membership_plan.Listings_remains) && !empty($membership_plan.Listing_number)} overdue{/if}">
                                                {if empty($membership_plan.Listing_number)}
                                                    <b>{$lang.unlimited_short}</b>
                                                {else}
                                                    {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                                                    {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                                                    {$lang.rest_of_amount|replace:$rRest:$membership_plan.Listings_remains|replace:$rAmount:$membership_plan.Listing_number}
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                {/if}

                                {if $membership_plan.Subscription}
                                    <a class="mt-3 unsubscription button" id="unsubscription-{$account_info.ID}" href="javascript:void(0);" accesskey="{$account_info.ID}-{$membership_plan.Subscription_ID}-{$membership_plan.Subscription_service}"><span>{$lang.unsubscription}</span>&nbsp;</a>
                                {else}
                                    {if $account_info.free_renew}
                                        <a class="mt-3 button w-100 w-md-auto"
                                           href="{pageUrl key=$pageInfo.Key add_url='step=renew' vars='info=membership'}">
                                            {$lang.renew}
                                        </a>
                                    {elseif $account_info.paid_renew}
                                        <a class="mt-3 button w-100 w-md-auto proceed-to-checkout" href="javascript://">
                                            {if $account_info.Payment_status == 'unpaid'}
                                                {$lang.proceed_checkout}
                                            {else}
                                                {$lang.renew}
                                            {/if}
                                        </a>
                                    {/if}
                                {/if}
                            </div>
                        </li>
                    </ul>
                {else}
                    <div class="text-notice">{$lang.notice_no_membership_plans_selected}</div>

                    {if $ms_plans_total > 0}
                        <a href="{pageUrl key='my_profile' add_url='step=purchase'}" class="button">{$lang.select_plan}</a>
                    {/if}
                {/if}
            </div>

            <div class="d-none profile-payment">
                <form class="mt-4" id="form-checkout" name="payment" method="post" action="{pageUrl key=$pageInfo.Key add_url='step=renew' vars='info=membership'}">
                    <input type="hidden" name="step" value="checkout" />
                    {gateways}

                    <div class="mt-3">
                        <input type="submit" value="{$lang.checkout}" />
                        <a class="red margin close" href="javascript://">{$lang.cancel}</a>
                    </div>
                </form>
            </div>

            <script class="fl-js-dynamic">
            {literal}

            $(function(){
                $('.unsubscription').each(function() {
                    $(this).flModal({
                        caption: '',
                        content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
                        prompt: 'flSubscription.cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +', false)',
                        width: 'auto',
                        height: 'auto'
                    });
                });

                var $profilePayment = $('.profile-payment');

                $('.proceed-to-checkout').click(function(){
                    $profilePayment.toggleClass('d-none');

                    if (!$profilePayment.hasClass('d-none')) {
                        flynax.slideTo('.profile-payment');
                    }
                });

                $profilePayment.find('.close').click(function(){
                    $profilePayment.addClass('d-none');
                });
            });

            {/literal}
            </script>
        {/if}
        <!-- membership end -->
    </div>

    {rlHook name='profileBlock'}
{/if}
{if $membership_plan.Subscription}
    {addJS file=$rlTplBase|cat:'js/subscription.js' id='subscription'}
{/if}
<!-- my profile end -->
