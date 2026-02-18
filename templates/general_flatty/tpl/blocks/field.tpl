<!-- fields block -->

<script>if (typeof window.textarea_fields == 'undefined') window.textarea_fields = new Array();</script>

{foreach from=$fields item='field'}
    {if $field.Add_page}
        {assign var='fKey' value=$field.Key}
        {assign var='fVal' value=$smarty.post.f}

        {assign var='cell_class' value='single-field'}
        {if $field.Type == 'price' || $field.Type == 'mixed'}
            {assign var='cell_class' value='combo-field'}
        {elseif $field.Type == 'checkbox'}
            {assign var='cell_class' value='checkbox-field'}
        {elseif $field.Type == 'radio'}
            {if $field.Values|@count <= 2}
                {assign var='cell_class' value='inline-fields'}
            {else}
                {assign var='cell_class' value='checkbox-field'}
            {/if}
        {elseif $field.Type == 'bool'}
            {assign var='cell_class' value='inline-fields'}
        {elseif $field.Type == 'date'}
            {assign var='cell_class' value='two-fields'}
        {elseif $field.Key == 'Category_ID' && $listing_types[$group.Listing_type].Search_multi_categories}
            {assign var="levels_number" value=$listing_types[$group.Listing_type].Search_multicat_levels}
            {if $levels_number == 2}
                {assign var='cell_class' value='two-fields'}
            {elseif $levels_number > 2}
                {assign var='cell_class' value='three-field'}
            {/if}
        {elseif $field.Type == 'phone'}
            {assign var='cell_class' value=$cell_class|cat:' phone'}
        {elseif $field.Type == 'accept'}
            {assign var='cell_class' value='inline-fields'}
        {/if}

        {if $field.Map && $account_address[$field.Key]}
            <input type="hidden" class="on_map_data" data-field-key="{$field.Key}" value="{$account_address[$field.Key]}" />
        {/if}

        {customFieldHandler field=$field assign='use_custom'}

        {assign var='default_value' value=''}

        {if !$use_custom}
            <div class="submit-cell"{if $field.Required} data-required="true"{/if}>
                <div class="name">
                    {$lang[$field.pName]}
                    {if $field.Required}
                        <span class="red">&nbsp;*</span>
                    {/if}
                    {if !empty($lang[$field.pDescription])}
                        <img class="qtip" alt="" title="{$lang[$field.pDescription]}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
                    {/if}
                </div>
                <div class="field {$cell_class}{if $field.Map && $field.Key != 'account_address_on_map'} on_map{/if}" id="sf_field_{$field.Key}">

                {if $field.Type == 'text'}
                    {if ($field.Multilingual && $languages|@count > 1) || is_array($fVal.$fKey)}
                        <ul class="tabs tabs-hash">
                            {foreach from=$languages item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                    <a href="#{$fKey}_{$language.Code}" data-target="{$fKey}_{$language.Code}">{$language.name}</a>
                                </li>
                            {/foreach}
                        </ul>
                        <div class="ml_tabs_content light-inputs">
                            {foreach from=$languages item='language' name='langF'}
                            <div lang="{$language.Code}" {if !$smarty.foreach.langF.first}class="hide"{/if} id="area_{$fKey}_{$language.Code}">
                                {if $fVal.$fKey[$language.Code]}
                                    {assign var='default_value' value=$fVal.$fKey[$language.Code]}
                                {elseif $field.pMultiDefault}
                                    {assign var='default_value' value=$field.pMultiDefault[$language.Code]}
                                {elseif $field.Default && $lang[$field.pDefault]}
                                    {assign var='default_value' value=$lang[$field.pDefault]}
                                {else}
                                    {assign var='default_value' value=''}
                                {/if}

                                <input type="text" name="f[{$field.Key}][{$language.Code}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" />
                            </div>
                            {/foreach}
                        </div>
                    {else}
                        {if $fVal.$fKey}
                            {assign var='default_value' value=$fVal.$fKey}
                        {elseif $field.Default}
                            {assign var='default_value' value=$lang[$field.pDefault]}
                        {/if}
                        <input type="text" name="f[{$field.Key}]" {if $field.Values < 100}size="{$field.Values}" class="wauto"{/if} maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" />
                    {/if}
                {elseif $field.Type == 'textarea'}
                    {if ($field.Multilingual && $languages|@count > 1) || is_array($fVal.$fKey)}
                        <ul class="tabs tabs-hash">
                            {foreach from=$languages item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                    <a href="#{$fKey}_{$language.Code}" data-target="{$fKey}_{$language.Code}">{$language.name}</a>
                                </li>
                            {/foreach}
                        </ul>
                        <div class="ml_tabs_content">
                            {foreach from=$languages item='language' name='langF'}
                            <div lang="{$language.Code}" {if !$smarty.foreach.langF.first}class="hide"{/if} id="area_{$fKey}_{$language.Code}">
                                <textarea data-type="{$field.Condition}" rows="5" cols="" name="f[{$field.Key}][{$language.Code}]" id="textarea_{$field.Key}_{$language.Code}">{strip}
                                {if $fVal.$fKey[$language.Code]}
                                    {$fVal.$fKey[$language.Code]}
                                {/if}
                                {/strip}</textarea>

                                <script>
                                if (!window.textarea_fields['textarea_{$field.Key}_{$language.Code}']) {literal} { {/literal}
                                    window.textarea_fields['textarea_{$field.Key}_{$language.Code}'] = {literal} { {/literal}
                                        type: '{$field.Condition}',
                                        length: '{$field.Values}'
                                    {literal} } {/literal};
                                {literal} } {/literal};
                                </script>
                            </div>
                            {/foreach}
                        </div>
                    {else}
                        <textarea data-type="{$field.Condition}" rows="5" cols="" name="f[{$field.Key}]" id="textarea_{$field.Key}">{strip}
                        {if $fVal.$fKey}
                            {$fVal.$fKey}
                        {elseif $field.Default}
                            {$lang[$field.pDefault]}
                        {/if}
                        {/strip}</textarea>

                        <script>
                        if (!window.textarea_fields['textarea_{$field.Key}']) {literal} { {/literal}
                            window.textarea_fields['textarea_{$field.Key}'] = {literal} { {/literal}
                                type: '{$field.Condition}',
                                length: '{$field.Values}'
                            {literal} } {/literal};
                        {literal} } {/literal};
                        </script>
                    {/if}
                {elseif $field.Type == 'number'}
                    <input class="numeric wauto" type="text" name="f[{$field.Key}]" size="{if $field.Values}{$field.Values}{else}18{/if}" maxlength="{if $field.Values}{$field.Values}{else}18{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{/if} />
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
                {elseif $field.Type == 'date'}
                    {if $field.Default == 'single'}
                        <input class="date"
                            type="text"
                            id="date_{$field.Key}"
                            name="f[{$field.Key}]"
                            maxlength="10"
                            value="{$fVal.$fKey}"
                            autocomplete="off" />

                        <script class="fl-js-dynamic">{literal}
                        $(document).ready(function(){
                            $('#date_{/literal}{$field.Key}{literal}')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                        });
                        {/literal}</script>
                    {elseif $field.Default == 'multi'}
                        <input placeholder="{$lang.from}"
                            class="date"
                            type="text"
                            id="date_{$field.Key}_from"
                            name="f[{$field.Key}][from]"
                            maxlength="10"
                            value="{$fVal.$fKey.from}"
                            autocomplete="off" />

                        <input placeholder="{$lang.to}"
                            class="date"
                            type="text"
                            id="date_{$field.Key}_to"
                            name="f[{$field.Key}][to]"
                            maxlength="10"
                            value="{$fVal.$fKey.to}"
                            autocomplete="off" />

                        <script class="fl-js-dynamic">{literal}
                        $(document).ready(function(){
                            $('#date_{/literal}{$field.Key}{literal}_from')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                            $('#date_{/literal}{$field.Key}{literal}_to')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                        });
                        {/literal}</script>
                    {/if}
                {elseif $field.Type == 'mixed' || $field.Type == 'unit'}
                    <input class="numeric" type="text" name="f[{$field.Key}][value]" size="8" maxlength="15" {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if} />
                    {if !empty($field.Condition)}
                        {assign var='df_source' value=$field.Condition|df}
                    {else}
                        {assign var='df_source' value=$field.Values}
                    {/if}

                    {if $df_source && $df_source|@count > 1}
                        <select name="f[{$field.Key}][df]">
                            {foreach from=$df_source item='df_item' key='df_key'}
                                <option value="{$df_item.Key}" {if ($df_item.Key == $fVal.$fKey.df) || (!$fVal.$fKey.df && $df_item.Default)}selected="selected"{/if}>{$lang[$df_item.pName]}</option>
                            {/foreach}
                        </select>
                    {else}
                        <input type="hidden" name="f[{$field.Key}][df]" value="{foreach from=$df_source item='mItem'}{$mItem.Key}{assign var='mixed_cur_val' value=$mItem.pName}{break}{/foreach}" />
                        <span>{$lang[$mixed_cur_val]}</span>
                    {/if}
                {elseif $field.Type == 'price'}
                    {if $field.Opt1}
                        <span class="starting-price-text pr-2 {if !$fVal.$fKey.from}hide{/if}">{$lang.price_from}</span>
                    {/if}

                    <input class="numeric price-full"
                            type="text"
                            name="f[{$field.Key}][value]"
                            size="8"
                            maxlength="15"
                            {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if}
                    />

                    {assign var='currency' value='currency'|df}
                    {if $currency && $currency|@count > 1}
                        <select name="f[{$field.Key}][currency]">
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
                                    <div class="d-flex align-items-center{if $smarty.foreach.priceF.iteration > 1} mt-2{/if}">
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
                                            <img class="qtip ml-3" alt="" title="{$lang.with_popup_desc}" src="{$rlTplBase}img/blank.gif" />
                                        {/if}
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    {/if}
                {elseif $field.Type == 'bool'}
                    <span class="custom-input">
                        <label>
                            <input type="radio" value="1" name="f[{$field.Key}]" {if $fVal.$fKey == '1'}checked="checked"{elseif $field.Default}checked="checked"{/if} />
                            {$lang.yes}
                        </label>
                    </span>
                    <span class="custom-input">
                        <label>
                            <input type="radio" value="0" name="f[{$field.Key}]" {if $fVal.$fKey == '0'}checked="checked"{elseif !$field.Default && !$fVal.$fKey}checked="checked"{/if}/>
                            {$lang.no}
                        </label>
                    </span>
                {elseif $field.Type == 'select'}
                    {rlHook name='tplListingFieldSelect'}
                    <select class="{if $field.Key == 'year'}w120{/if}{if $field.Autocomplete} select-autocomplete{/if}" name="f[{$field.Key}]">
                        <option value="0">{$lang.select}</option>

                        {foreach from=$field.Values item='option' key='key'}
                            {if $field.Condition}
                                {assign var='key' value=$option.Key}
                            {/if}
                            <option value="{if $field.Condition}{$option.Key}{else}{$key}{/if}" {if $fVal.$fKey}{if $fVal.$fKey == $key}selected="selected"{/if}{else}{if ($field.Default == $key) || $option.Default }selected="selected"{/if}{/if}>{if $field.Condition == 'years'}{$option.name}{else}{$lang[$option.pName]}{/if}</option>
                        {/foreach}
                    </select>
                {elseif $field.Type == 'checkbox'}
                    {assign var='fDefault' value=$field.Default}
                    {if $field.Opt2}{math assign='col_count' equation='12 / opt' opt=$field.Opt2}{/if}
                    <input type="hidden" name="f[{$field.Key}][0]" value="0" />

                    <div class="row">
                    {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                        {if $field.Condition}
                            {assign var='key' value=$option.Key}
                        {/if}
                        <span class="custom-input col-xs-12 {if $col_count}col-sm-{$col_count}{else}col-lg-4 col-md-6 col-sm-4{/if}">
                            <label title="{$lang[$option.pName]}">
                                    <input type="checkbox" {if is_array($fVal.$fKey)}{if $key|in_array:$fVal.$fKey}checked="checked"{/if}{else}{if $option.Default || ($field.Default && is_numeric($key|array_search:$field.Default))}checked="checked"{/if}{/if} value="{$key}" name="f[{$field.Key}][{$key}]" />
                                {$lang[$option.pName]}
                            </label>
                        </span>
                    {/foreach}
                    </div>

                    <div class="checkbox_bar"><a href="javascript:void(0)" onclick="$(this).parent().prev().find('input[type=checkbox]').prop('checked', true)">{$lang.check_all}</a> / <a onclick="$(this).parent().prev().find('input[type=checkbox]').prop('checked', false)" href="javascript:void(0)">{$lang.uncheck_all}</a></div>
                {elseif $field.Type == 'radio'}
                    <input type="hidden" value="0" name="f[{$field.Key}]" />

                    {if $field.Values && $field.Values|@count > 2}<div class="row">{/if}
                    {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                        {if $field.Condition}
                            {assign var='key' value=$option.Key}
                        {/if}

                        <span class="custom-input{if $field.Values && $field.Values|@count > 2} col-xs-12 col-sm-6 col-md-4{/if}">
                            <label title="{$lang[$option.pName]}">
                                <input type="radio" value="{$key}" name="f[{$field.Key}]" {if $fVal.$fKey}{if $fVal.$fKey == $key}checked="checked"{/if}{else}{if ($field.Default == $key) || $option.Default}checked="checked"{/if}{/if} />
                                {$lang[$option.pName]}
                            </label>
                        </span>
                    {/foreach}
                    {if $field.Values && $field.Values|@count > 2}</div>{/if}
                {elseif $field.Type == 'file' || $field.Type == 'image'}
                    {assign var="field_value" value=''}

                    {if $fVal.$fKey}
                        {assign var="field_value" value=$fVal[$fKey]}
                    {elseif $smarty.post.f_sys_exist.$fKey}
                        {assign var="field_value" value=$smarty.post.f_sys_exist.$fKey}
                    {/if}

                    {if $field_value}
                        {if $field.Opt1}
                            <div class="uploaded-files d-flex mb-4 flex-wrap">
                                {foreach from=','|explode:$field_value item='file'}
                                    {assign var='file_info' value=$file|pathinfo}
                                    <div class="d-flex flex-column uploaded-file mr-3 file-data"
                                         data-field="{$field.Key}"
                                         data-multipart="1"
                                         data-id="{$itemId}"
                                         data-value="{$file}"
                                         data-type="listing">
                                        <span class="uploaded-file__ext d-flex flex-column justify-content-center align-items-center hlight hborder">
                                            {$file_info.extension}
                                            <a href="javascript://" class="remove-file">
                                                <img class="delete icon mt-2"
                                                     src="{$rlTplBase}img/blank.gif"
                                                     alt=""
                                                     title="{$lang.delete}" />
                                            </a>
                                        </span>
                                        <span class="font-size-xs">
                                            <a href="{$smarty.const.RL_FILES_URL}{$file}" target="_blank" class="d-flex mt-1 text-center text-break justify-content-center">{$file_info.filename}</a>
                                        </span>
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            {if !$field.Key|files}
                                <div id="{$field.Key}_file"
                                    class="image-field-preview file-data"
                                    data-field="{$field.Key}"
                                    data-value="{$field_value}"
                                    data-type="listing">
                                    <div class="relative fleft">
                                        <input type="hidden" name="f[sys_exist_{$field.Key}]" value="{$field_value}" />

                                        <div class="fleft" style="margin-bottom: 5px;">
                                            <div>
                                                <table class="sTable">
                                                    <tr>
                                                        <td>{$lang.currently_uploaded_file}</td>
                                                        <td class="ralign">
                                                            <a href="javascript://" id="delete_{$field.Key}" class="remove-file">
                                                                {$lang.remove}
                                                                <img id="delete_{$field.Key}" class="delete icon"
                                                                src="{$rlTplBase}img/blank.gif" alt="" title="{$lang.delete}" />
                                                            </a>
                                                        </td></tr>
                                                </table>
                                            </div>
                                            <span style="font-style:italic;" class="dark_13" title="{$field_value}">
                                                <b>{$field_value}</b>
                                            </span>
                                        </div>
                                        <div class="clear"></div>

                                        {if $field.Type == 'image'}
                                            <img style="width: auto;" class="thumbnail" title="{$field.name}"
                                                alt="{$field.name}" src="{$smarty.const.RL_FILES_URL}{$field_value}" />
                                        {/if}
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            {else}
                                {getTmpFile field=$field.Key parent='f'}
                            {/if}
                        {/if}
                    {/if}

                    {assign var='field_type' value=$field.Default}
                    <div class="file-input">
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
                        <input type="text" class="file-name" name="" />
                        <span>{$lang.choose}</span>
                    </div>

                    {if $field.Type == 'file' && $field.Default && $field.Default != 'mixed'}
                        <em>{$l_file_types.$field_type.name} (.{$l_file_types.$field_type.ext|replace:',':', .'})</em>
                    {elseif $field.Default && $field.Default == 'mixed'}
                        {assign var='file_limit_replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
                        <em>{$lang.files_limit_hint|replace:$file_limit_replace:$field.Opt2}</em>
                    {/if}
                {elseif $field.Type == 'accept'}
                    <input class="hide"
                        value="0"
                        type="checkbox"
                        name="f[{$field.Key}]"
                        checked="checked" />

                    <label>
                        <input value="1"
                            type="checkbox"
                            name="f[{$field.Key}]"
                            {if $fVal.$fKey == '1'}checked="checked"{/if} />
                        &nbsp;{$lang.agree}

                        <a target="_blank" href="{pageUrl key=$field.Default}">
                            {phrase key='pages+name+'|cat:$field.Default}
                        </a>
                    </label>
                {/if}

                </div>
            </div>
        {/if}
    {/if}
{/foreach}

<!-- fields block end -->
