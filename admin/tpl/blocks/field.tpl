<!-- fields block ( for insert ) -->

<table class="form">

{foreach from=$fields item='field'}
    {assign var='fKey' value=$field.Key}
    {assign var='fVal' value=$smarty.post.f}

    <tr {if $field.Map && $field.Key != 'account_address_on_map'}class="on_map"{/if}>
        <td class="name">
            {if $field.Required}
                <span class="red">*</span>
            {/if}
            {$lang[$field.pName]}

            {if $lang[$field.pDescription]}
                <img class="qtip" alt="" src="{$rlTplBase}img/blank.gif" title="{$lang[$field.pDescription]}" />
            {/if}
        </td>
        <td class="field {if $field.Condition == 'html'}ckeditor{/if}">

        {if $field.Type == 'text'}
            {if ($field.Multilingual && $allLangs|@count > 1) || is_array($fVal.$fKey)}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>

                {foreach from=$allLangs item='language' name='langF'}
                    <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {if $fVal.$fKey[$language.Code]}
                            {assign var="default_value" value=$fVal.$fKey[$language.Code]}
                        {elseif $field.pMultiDefault}
                            {assign var="default_value" value=$field.pMultiDefault[$language.Code]}
                        {elseif $field.Default && $lang[$field.pDefault]}
                            {assign var="default_value" value=$lang[$field.pDefault]}
                        {else}
                            {assign var="default_value" value=''}
                        {/if}

                        <input class="w350" type="text" name="f[{$field.Key}][{$language.Code}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" /> <span class="field_description_noicon">{$language.name}</span>
                    </div>
                {/foreach}
            {else}
                <input class="w350" type="text" name="f[{$field.Key}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{elseif $field.Default}value="{$lang[$field.pDefault]}"{/if} />
            {/if}
        {elseif $field.Type == 'textarea'}
            <script type="text/javascript">var textarea_fields = new Array();</script>
            {if ($field.Multilingual && $allLangs|@count > 1) || is_array($fVal.$fKey)}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>

                {foreach from=$allLangs item='language' name='langF'}
                    <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {if $field.Condition == 'html'}<div class="hide">{if $fVal.$fKey[$language.Code]}{$fVal.$fKey[$language.Code]}{elseif $field.Default}{$lang[$field.default]}{/if}</div>{/if}
                        <textarea id="textarea_{$field.Key}_{$language.Code}" rows="5" cols="" class="resizable" name="f[{$field.Key}][{$language.Code}]">{if $field.Condition != 'html'}{if $fVal.$fKey[$language.Code]}{$fVal.$fKey[$language.Code]}{elseif $field.Default}{$lang[$field.default]}{/if}{/if}</textarea>
                        <script type="text/javascript">
                        textarea_fields.push('textarea_{$field.Key}_{$language.Code}');
                        </script>
                    </div>
                {/foreach}
            {else}
                {if $field.Condition == 'html'}<div class="hide">{if $fVal.$fKey}{$fVal.$fKey}{elseif $field.Default}{$lang[$field.default]}{/if}</div>{/if}
                <textarea id="textarea_{$field.Key}" rows="5" cols="" class="resizable" name="f[{$field.Key}]">{if $field.Condition != 'html'}{if $fVal.$fKey}{$fVal.$fKey}{elseif $field.Default}{$lang[$field.default]}{/if}{/if}</textarea>
                <script type="text/javascript">
                textarea_fields.push('textarea_{$field.Key}');
                </script>
            {/if}

            {if $field.Condition == 'html'}
            <script type="text/javascript">
            flynax.htmlEditor(
                textarea_fields,
                {if $field.Values}
                    [[
                        'wordcount',
                        {literal}{{/literal}
                            showParagraphs    : false,
                            showWordCount     : false,
                            showCharCount     : true,
                            maxCharCount      : {$field.Values},
                            countSpacesAsChars: true,
                        {literal}}{/literal}
                    ]]
                {else}[]{/if}
            );
            </script>
        {/if}
        {elseif $field.Type == 'number'}
            <input class="numeric w60" type="text" name="f[{$field.Key}]" size="{if $field.Values}{$field.Values}{else}10{/if}" maxlength="{if $field.Values}{$field.Values}{else}10{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{elseif $field.Default}value="{$field.default}"{/if} />
        {elseif $field.Type == 'phone'}
            <div class="gap-2 d-flex align-items-md-center flex-column flex-md-row">
                <input class="phone-field-valid" type="hidden" name="f[{$field.Key}][valid]" value="0" />
                <input class="phone-field-number" type="hidden" name="f[{$field.Key}][number]" value="{$fVal.$fKey.number}" />
                <input class="phone-field"
                       type="text"
                       name="f[{$field.Key}][template]"
                       data-show-dial="{$field.Opt1}"
                       data-show-flag="{$field.Condition}"
                       {if $field.Opt2}
                       data-country="{$field.Opt2|lower}"
                       {/if}
                       {if $fVal.$fKey.template || $fVal.$fKey.number}
                       value="{if $fVal.$fKey.template}{$fVal.$fKey.template}{else}{$fVal.$fKey.number}{/if}"
                       {/if}
                       />
                <span class="phone-field-error red hide"></span>
            </div>

            {if $field.Opt3 && ($config.whatsapp_phone_icon || $config.viber_phone_icon || $config.telegram_phone_icon)}
                <div class="phone-messengers mt-3 mb-3">
                    {if $config.whatsapp_phone_icon}
                        <span class="custom-input d-block phone-messengers__whatsapp">
                            <label title="{$lang.profile_has_whatsapp}">
                                <input type="checkbox"
                                       {if $fVal.$fKey.messengers.whatsapp}checked="checked"{/if}
                                       value="{$_phoneMessengers.whatsapp}"
                                       name="f[{$field.Key}][messengers][whatsapp]"
                                />
                                {$lang.profile_has_whatsapp}
                            </label>
                        </span>
                    {/if}
                    {if $config.viber_phone_icon}
                        <span class="custom-input d-block mt-2 phone-messengers__viber">
                            <label title="{$lang.profile_has_viber}">
                                <input type="checkbox"
                                       {if $fVal.$fKey.messengers.viber}checked="checked"{/if}
                                       value="{$_phoneMessengers.viber}"
                                       name="f[{$field.Key}][messengers][viber]"
                                />
                                {$lang.profile_has_viber}
                            </label>
                        </span>
                    {/if}
                    {if $config.telegram_phone_icon}
                        <span class="custom-input d-block mt-2 phone-messengers__telegram">
                            <label title="{$lang.profile_has_telegram}">
                                <input type="checkbox"
                                       {if $fVal.$fKey.messengers.telegram}checked="checked"{/if}
                                       value="{$_phoneMessengers.telegram}"
                                       name="f[{$field.Key}][messengers][telegram]"
                                />
                                {$lang.profile_has_telegram}
                            </label>
                        </span>
                    {/if}
                </div>
            {/if}

            <script>{literal}
            $(function(){
                flynax.phoneField();
            });
            {/literal}</script>
        {elseif $field.Type == 'date'}
            {if $field.Default == 'single'}
                <input type="text"
                    id="date_{$field.Key}"
                    name="f[{$field.Key}]"
                    maxlength="10"
                    class="date-calendar-inline"
                    value="{$fVal.$fKey}"
                    autocomplete="off" />

                <script type="text/javascript">
                {literal}
                $(document).ready(function(){
                    $('#date_{/literal}{$field.Key}{literal}').datepicker({
                        showOn         : 'both',
                        buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                        buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                        buttonImageOnly: true,
                        dateFormat     : 'yy-mm-dd',
                        changeMonth    : true,
                        changeYear     : true,
                        yearRange      : '-100:+30'
                    }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                });
                {/literal}
                </script>
            {elseif $field.Default == 'multi'}
                <table>
                <tr>
                    <td><label for="date_{$field.Key}_from" class="fLable">{$lang.from}</label></td>
                    <td style="width: 120px;">
                        <input type="text"
                            id="date_{$field.Key}_from"
                            name="f[{$field.Key}][from]"
                            maxlength="10"
                            class="date-calendar-inline"
                            value="{$fVal.$fKey.from}"
                            autocomplete="off" />
                    </td>
                    <td><label for="date_{$field.Key}_to" class="fLable">{$lang.to}</label></td>
                    <td style="width: 120px;">
                        <input type="text"
                            id="date_{$field.Key}_to"
                            name="f[{$field.Key}][to]"
                            maxlength="10"
                            class="date-calendar-inline"
                            value="{$fVal.$fKey.to}"
                            autocomplete="off" />
                    </td>
                </tr>
                </table>

                <script type="text/javascript">
                {literal}
                $(document).ready(function(){
                    $('#date_{/literal}{$field.Key}{literal}_from').datepicker({
                        showOn: 'both',
                        buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                        buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                        buttonImageOnly: true,
                        dateFormat     : 'yy-mm-dd',
                        changeMonth    : true,
                        changeYear     : true,
                        yearRange      : '-100:+30'
                    }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                    $('#date_{/literal}{$field.Key}{literal}_to').datepicker({
                        showOn: 'both',
                        buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                        buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                        buttonImageOnly: true,
                        dateFormat     : 'yy-mm-dd',
                        changeMonth    : true,
                        changeYear     : true,
                        yearRange      : '-100:+30'
                    }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                });
                {/literal}
                </script>
            {/if}
        {elseif $field.Type == 'mixed'}
            <input class="numeric float" type="text" name="f[{$field.Key}][value]" size="8" maxlength="15" {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if} style="width: 70px;" />

            {if !empty($field.Condition)}
                {assign var='df_source' value=$field.Condition|df}
            {else}
                {assign var='df_source' value=$field.Values}
            {/if}

            {if $df_source|@count > 1}
                <select class="float lm" name="f[{$field.Key}][df]" style="width: 80px;margin-bottom: 0;">
                    {foreach from=$df_source item='df_item'}
                        <option value="{$df_item.Key}" {if ($df_item.Key == $fVal.$fKey.df) || (!$fVal.$fKey.df && $df_item.Default)}selected="selected"{/if}>{$lang[$df_item.pName]}</option>
                    {/foreach}
                </select>
            {else}
                <input type="hidden" name="f[{$field.Key}][df]" value="{foreach from=$df_source item='df_item'}{$df_item.Key}{/foreach}" />
                {foreach from=$df_source item='df_item'}{$lang[$df_item.pName]}{/foreach}
            {/if}

        {elseif $field.Type == 'price'}
            {if $field.Opt1}
                <span class="starting-price-text {if !$fVal.$fKey.from}hide{/if}">{$lang.price_from}</span>
            {/if}

            <input class="numeric float price-full"
                    type="text"
                    name="f[{$field.Key}][value]"
                    size="8"
                    maxlength="15"
                    {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if}
                    style="width: 70px;"
            />

            {assign var='currency' value='currency'|df}
            {if $currency && $currency|@count > 1}
                <select class="float lm" name="f[{$field.Key}][currency]" style="width: 60px;">
                    {foreach from=$currency item='currency_item'}
                        <option value="{$currency_item.Key}"
                                {if ($currency_item.Key == $fVal.$fKey.currency)
                                    || (!$fVal.$fKey.currency && $currency_item.Default)
                                }selected="selected"{/if}
                        >{$lang[$currency_item.pName]}</option>
                    {/foreach}
                </select>
            {else}
                <input type="hidden"
                        name="f[{$field.Key}][currency]"
                        value="{foreach from=$currency item='cItem'}{$cItem.Key}{assign var='price_cur_val' value=$cItem.name}{break}{/foreach}"
                />
                <span>{$price_cur_val}</span>
            {/if}

            {if $field.Opt1}
                <div class="mt-2 price-options">
                    <div>
                        <label>
                            <input class="starting-price"
                                    type="checkbox"
                                    value="1"
                                    name="f[{$field.Key}][from]"
                                    {if $fVal.$fKey.from}checked="checked"{/if}
                            >
                            {$lang.starting_price}
                        </label>
                    </div>
                    <div class="mt-3">{$lang.or}</div>
                    <div class="mt-3 mb-2">
                        {foreach from='price_options'|df item='price_option' name='priceF'}
                            <div {if $smarty.foreach.priceF.iteration > 1}class="mt-2"{/if}>
                                <label>
                                    <input class="price-option"
                                            type="radio"
                                            value="{$price_option.Key}"
                                            name="f[{$field.Key}][option]"
                                            {if $fVal.$fKey.option == $price_option.Key}checked="checked"{/if}
                                    >
                                    {$price_option.name}
                                </label>

                                {if $field.Contact_form_option && $field.Contact_form_option == $price_option.Key}
                                    <img class="qtip" alt="" title="{$lang.with_popup_desc}" src="{$rlTplBase}img/blank.gif" />
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}
        {elseif $field.Type == 'bool'}
            <label><input type="radio" value="1" name="f[{$field.Key}]" {if $fVal.$fKey == '1' || (!$fVal.$fKey && $field.Default == '1') }checked="checked"{/if} /> {$lang.yes}</label>
            <label><input type="radio" value="0" name="f[{$field.Key}]" {if $fVal.$fKey == '0' || (!$fVal.$fKey && $field.Default == '0') }checked="checked"{/if} /> {$lang.no}</label>
        {elseif $field.Type == 'select'}
            {rlHook name='apTplListingFieldSelect'}
            <select name="f[{$field.Key}]" {if $field.Condition == 'years'}style="width: 110px;"{/if}{if $field.Autocomplete} class="select-autocomplete"{/if}>
                <option value="0">{$lang.select}</option>
                {foreach from=$field.Values item='option' key='key'}
                    {if $field.Condition}
                        {assign var='key' value=$option.Key}
                    {/if}
                    <option value="{if $field.Condition}{$option.Key}{else}{$key}{/if}" {if $fVal.$fKey}{if $fVal.$fKey == $key}selected="selected"{/if}{else}{if $field.Default == $key || $option.Default}selected="selected"{/if}{/if}>{if $field.Condition == 'years'}{$option.name}{else}{$lang[$option.pName]}{/if}</option>
                {/foreach}
            </select>
        {elseif $field.Type == 'checkbox'}
            {if $field.Opt2}
                {assign var='col_num' value=$field.Opt2}
            {else}
                {assign var='col_num' value=3}
            {/if}
            {assign var='fDefault' value=$field.Default}
            <input type="hidden" name="f[{$field.Key}][0]" value="0" />
            <table {if $col_num > 2} class="fixed"{/if}>
            <tr>
            {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                {if !empty($field.Condition)}
                    {assign var="key" value=$option.Key}
                {/if}
                <td style="padding: 2px 0;">
                    <label><input type="checkbox" value="{$key}" {if is_array($fVal.$fKey)}{foreach from=$fVal.$fKey item='chVals'}{if $chVals == $key}checked="checked"{/if}{/foreach}{else}{foreach from=$field.Default item='chDef'}{if $chDef == $key && !empty($chDef)}checked="checked"{/if}{/foreach}{/if} name="f[{$field.Key}][{$key}]" /> {$lang[$option.pName]}</label>
                </td>
                {if $smarty.foreach.checkboxF.iteration%$col_num == 0 && !$smarty.foreach.checkboxF.last}
                </tr>
                <tr>
                {/if}
            {/foreach}
            </tr>
            </table>
            <div style="padding: 2px 0 5px;"><a href="javascript:void(0)" onclick="$(this).parent().prev().find('input[type=checkbox]').prop('checked', true)">{$lang.check_all}</a> / <a onclick="$(this).parent().prev().find('input[type=checkbox]').prop('checked', false)" href="javascript:void(0)">{$lang.uncheck_all}</a></div>
        {elseif $field.Type == 'radio'}
            <input type="hidden" value="0" name="f[{$field.Key}]" />
            <table id="{$field.Key}_table">
            <tr>
            {foreach from=$field.Values item='option' key='key' name='radioF'}
                {if $field.Condition}
                    {assign var='key' value=$option.Key}
                {/if}
                <td {if $smarty.foreach.radioF.total > 3}style="width: 33%"{/if}>
                    <label><input type="radio" value="{$key}" name="f[{$field.Key}]" {if $fVal.$fKey}{if $fVal.$fKey == $key}checked="checked"{/if}{else}{if $field.Default == $key}checked="checked"{/if}{/if} /> {$lang[$option.pName]}</label>
                </td>
                {if $smarty.foreach.radioF.iteration%3 == 0 && !$smarty.foreach.radioF.last}
                </tr>
                <tr>
                {/if}
            {/foreach}
            </tr>
            </table>
        {elseif $field.Type == 'file' || $field.Type == 'image'}
            {assign var='field_type' value=$field.Default}
            <input type="hidden" name="f[{$field.Key}]" value="" />

            {assign var="field_value" value=''}

            {if $fVal.$fKey}
                {assign var="field_value" value=$fVal[$fKey]}
            {elseif $smarty.post.f_sys_exist.$fKey}
                {assign var="field_value" value=$smarty.post.f_sys_exist.$fKey}
            {/if}

            {if $field_value}
                {if $field.Opt1}
                    <div class="uploaded-files" style="margin-bottom: 11px;">
                        {foreach from=','|explode:$field_value item='file'}
                            {assign var='file_info' value=$file|pathinfo}
                            <div class="uploaded-file file-data"
                                 style="margin-bottom: 4px;"
                                 data-field="{$field.Key}"
                                 data-multipart="1"
                                 data-id="{$itemId}"
                                 data-value="{$file}"
                                 data-type="listing">

                                <a href="{$smarty.const.RL_FILES_URL}{$file}" target="_blank">{$file_info.basename}</a>
                                |
                                <a href="javascript://" class="remove-file">{$lang.delete}</a>
                            </div>
                        {/foreach}
                    </div>
                {else}
                    {if !$field.Key|files}
                        <div class="image-field-preview file-data"
                             data-field="{$field.Key}"
                             data-value="{$field_value}"
                             data-type="listing"
                             style="padding: 0 0 5px 0;">
                            <input type="hidden" name="f[sys_exist_{$field.Key}]" value="{$field_value}" />

                            {if $field.Type == 'file'}
                                <a href="{$smarty.const.RL_FILES_URL}{$field_value}">{$lang.download}</a>
                                |
                                <a class="remove-file" href="javascript:void(0)">{$lang.delete}</a>
                            {else}
                                <div class="relative fleft">
                                    <img style="width: auto;height: auto;" class="thumbnail" title="{$field.name}" alt="{$field.name}" src="{$smarty.const.RL_FILES_URL}{$field_value}" />
                                    <img class="delete_item remove-file" style="display: block;" src="{$rlTplBase}img/blank.gif" alt="" title="{$lang.delete}" />
                                </div>
                                <div class="clear"></div>
                            {/if}
                        </div>
                    {else}
                        {getTmpFile field=$field.Key parent="f"}
                    {/if}
                {/if}
            {/if}

            {assign var='field_type' value=$field.Default}
            <input type="hidden" name="f[{$field.Key}]" value="{$fVal.$fKey}" />
            <input class="file"
                   type="file"
                   {if $field.Opt1}
                   accept=".{$l_file_types.$field_type.ext|replace:',':',.'}"
                   multiple
                   name="{$field.Key}[]"
                   {else}
                   name="{$field.Key}"
                   {/if} />

            {if $field.Type == 'file' && !empty($field.Default)}<span class="green_11"> <em>{$l_file_types.$field_type.name} (.{$l_file_types.$field_type.ext|replace:',':', .'})</em></span>{/if}
        {elseif $field.Type == 'accept'}
            <input class="hide" value="0" type="checkbox" name="f[{$field.Key}]" checked="checked" />

            <label class="fLable">
                <input value="1" type="checkbox" name="f[{$field.Key}]" {if $fVal.$fKey == '1'}checked="checked"{/if} />
                &nbsp;{$lang.agree}

                <a target="_blank" href="{pageUrl key=$field.Default}">
                    {phrase key='pages+name+'|cat:$field.Default}
                </a>
            </label>
        {/if}
        </td>
    </tr>

{/foreach}

</table>

<!-- fields block ( for insert ) end -->
