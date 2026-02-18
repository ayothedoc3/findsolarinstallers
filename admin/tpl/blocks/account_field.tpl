<!-- account fields add -->

<table class="form">
{foreach from=$fields item='field'}
    {assign var='fKey' value=$field.Key}
    {assign var='fVal' value=$smarty.post.f}

    <tr>
        <td class="name">
            {$field.name}
            {if $field.Required}
                <span class="red">*</span>
            {/if}
            {if !empty($field.description)}
                <img alt="" class="qtip" title="{$field.description}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
            {/if}
        </td>
        <td class="field">
            {if $field.Type == 'text'}
                {if $field.Multilingual && $allLangs|@count > 1}
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

                        <input class="w250" type="text" name="f[{$field.Key}][{$language.Code}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" /> <span class="field_description_noicon">{$language.name}</span>
                    </div>
                    {/foreach}
                {else}
                    <input class="w250" type="text" name="f[{$field.Key}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{elseif $field.Default}value="{$lang[$field.pDefault]}"{/if} />
                {/if}
            {elseif $field.Type == 'textarea'}
                <script type="text/javascript">var textarea_fields = new Array();</script>
                <div class="hide eval">
                    var textarea_fields = new Array();
                </div>

                {if $field.Multilingual && $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>

                    {foreach from=$allLangs item='language' name='langF'}
                    <div class="ckeditor tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {if $field.Condition == 'html'}<div class="hide">{if $fVal.$fKey[$language.Code]}{$fVal.$fKey[$language.Code]}{elseif $field.Default}{$lang[$field.pDefault]}{/if}</div>{/if}
                        <textarea rows="5" cols="" name="f[{$field.Key}][{$language.Code}]" id="textarea_{$field.Key}_{$language.Code}">{if $field.Condition != 'html'}{if $fVal.$fKey[$language.Code]}{$fVal.$fKey[$language.Code]}{elseif $field.Default}{$lang[$field.pDefault]}{/if}{/if}</textarea>
                        <script type="text/javascript">
                        textarea_fields.push('textarea_{$field.Key}_{$language.Code}');
                        {if $field.Condition != 'html'}
                        {literal}

                        $(document).ready(function(){
                            $('#textarea_{/literal}{$field.Key}_{$language.Code}{literal}').textareaCount({
                                'maxCharacterSize': {/literal}{$field.Values}{literal},
                                'warningNumber': 20
                            })
                        });

                        {/literal}
                        {/if}
                        </script>

                        {if $field.Condition != 'html'}
                            <div class="hide eval">
                                {literal}
                                $('#textarea_{/literal}{$field.Key}{literal}').textareaCount({
                                    'maxCharacterSize': {/literal}{$field.Values}{literal},
                                    'warningNumber': 20
                                });
                                {/literal}
                            </div>
                        {else}
                            <div class="hide eval">
                                textarea_fields.push('textarea_{$field.Key}_{$language.Code}');
                            </div>
                        {/if}
                    </div>
                    {/foreach}
                {else}
                    {if $field.Condition == 'html'}<div class="hide">{if $fVal.$fKey}{$fVal.$fKey}{elseif $field.Default}{$lang[$field.pDefault]}{/if}</div>{/if}
                    <textarea rows="5" cols="" name="f[{$field.Key}]" id="textarea_{$field.Key}">{if $field.Condition != 'html'}{if $fVal.$fKey}{$fVal.$fKey}{elseif $field.Default}{$lang[$field.pDefault]}{/if}{/if}</textarea>
                    <script type="text/javascript">
                    textarea_fields.push('textarea_{$field.Key}');
                    {if $field.Condition != 'html'}
                    {literal}

                    $(document).ready(function(){
                        $('#textarea_{/literal}{$field.Key}{literal}').textareaCount({
                            'maxCharacterSize': {/literal}{$field.Values}{literal},
                            'warningNumber': 20
                        })
                    });

                    {/literal}
                    {/if}
                    </script>

                    {if $field.Condition != 'html'}
                        <div class="hide eval">
                            {literal}
                            $('#textarea_{/literal}{$field.Key}{literal}').textareaCount({
                                'maxCharacterSize': {/literal}{$field.Values}{literal},
                                'warningNumber': 20
                            });
                            {/literal}
                        </div>
                    {else}
                        <div class="hide eval">
                            textarea_fields.push('textarea_{$field.Key}');
                        </div>
                    {/if}
                {/if}

                {if $field.Condition == 'html'}
                    <div class="hide eval">
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
                    </div>

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
                <input type="text" name="f[{$field.Key}]" size="{if $field.Values}{$field.Values}{else}10{/if}" maxlength="{if $field.Values}{$field.Values}{else}10{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{elseif $field.Default}value="{$field.default}"{/if} />
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
                        class="date-calendar"
                        value="{$fVal.$fKey}"
                        autocomplete="off" />

                    <script type="text/javascript">
                    {literal}
                    $(document).ready(function(){
                        $('#date_{/literal}{$field.Key}{literal}').datepicker({
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
                {elseif $field.Default == 'multi'}
                    <input type="text"
                        id="date_{$field.Key}_from"
                        name="f[{$field.Key}][from]"
                        maxlength="10"
                        class="date-calendar"
                        value="{$fVal.$fKey.from}"
                        autocomplete="off" />

                    <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />

                    <input type="text"
                        id="date_{$field.Key}_to"
                        name="f[{$field.Key}][to]"
                        maxlength="10"
                        class="date-calendar"
                        value="{$fVal.$fKey.to}"
                        autocomplete="off" />

                    <script type="text/javascript">
                    {literal}
                    $(document).ready(function(){
                        $('#date_{/literal}{$field.Key}{literal}_from').datepicker({
                            showOn         : 'both',
                            buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                            buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                            buttonImageOnly: true,
                            dateFormat     : 'yy-mm-dd',
                            changeMonth    : true,
                            changeYear     : true,
                            yearRange      : '-100:+30'
                        }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                        $('#date_{/literal}{$field.Key}{literal}_to').datepicker({
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
                {/if}
            {elseif $field.Type == 'mixed'}
                <input class="numeric" type="text" name="f[{$field.Key}][value]" size="8" maxlength="15" {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if} style="width: 70px;" />

                {if !empty($field.Condition)}
                    {assign var='df_source' value=$field.Condition|df}
                {else}
                    {assign var='df_source' value=$field.Values}
                {/if}

                {if $df_source|@count > 1}
                    <select name="f[{$field.Key}][df]" style="width: 60px;">
                        {foreach from=$df_source item='df_item' key='df_key'}
                            <option value="{$df_item.Key}" {if $fVal.$fKey.df}{if $df_item.Key == $fVal.$fKey.df}selected="selected"{/if}{else}{if $df_key == $field.Default}selected="selected"{/if}{/if}>{$lang[$df_item.pName]}</option>
                        {/foreach}
                    </select>
                {else}
                    <input type="hidden" name="f[{$field.Key}][df]" value="{foreach from=$df_source item='df_item'}{$df_item.Key}{/foreach}" />
                    {foreach from=$df_source item='df_item'}{$lang[$df_item.pName]}{/foreach}
                {/if}
            {elseif $field.Type == 'bool'}
                <label><input type="radio" value="1" name="f[{$field.Key}]" {if $fVal.$fKey}checked="checked"{elseif $field.Default}checked="checked"{/if} /> {$lang.yes}</label>
                <label><input type="radio" value="0" name="f[{$field.Key}]" {if !$fVal.$fKey}checked="checked"{elseif !$fVal.$fKey && !$field.Default}checked="checked"{/if} /> {$lang.no}</label>
            {elseif $field.Type == 'select'}
                {rlHook name='apTplAccountFieldSelect'}
                <select name="f[{$field.Key}]"{if $field.Autocomplete} class="select-autocomplete"{/if}>
                    <option value="0">{$lang.select}</option>

                    {foreach from=$field.Values item='option' key='key'}
                        {if $field.Condition}
                            {assign var='key' value=$option.Key}
                        {/if}
                        <option value="{if $field.Condition}{$option.Key}{else}{$key}{/if}" {if $fVal.$fKey}{if $fVal.$fKey == $key}selected="selected"{/if}{else}{if ($field.Default == $key) || $option.Default }selected="selected"{/if}{/if}>{if $option.name}{$option.name}{else}{$lang[$option.pName]}{/if}</option>
                    {/foreach}
                </select>
            {elseif $field.Type == 'checkbox'}
                {assign var='fDefault' value=$field.Default}
                <input type="hidden" name="f[{$field.Key}][0]" value="0" />
                <table>
                <tr>
                {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                    {if !empty($field.Condition)}
                        {assign var="key" value=$option.Key}
                    {/if}
                    <td {if $smarty.foreach.checkboxF.total > 5}style="width: 33%"{/if}>
                        <input type="checkbox" id="{$field.Key}_{$key}" value="{$key}" {if is_array($fVal.$fKey)}{foreach from=$fVal.$fKey item='chVals'}{if $chVals == $key}checked="checked"{/if}{/foreach}{else}{foreach from=$field.Default item='chDef'}{if $chDef == $key}checked="checked"{/if}{/foreach}{/if} name="f[{$field.Key}][{$key}]" /> <label for="{$field.Key}_{$key}" class="fLable">{$lang[$option.pName]}</label>
                    </td>
                    {if $smarty.foreach.checkboxF.iteration%3 == 0}
                    </tr>
                    <tr>
                    {/if}
                {/foreach}
                </tr>
                </table>
            {elseif $field.Type == 'radio'}
                <input type="hidden" value="0" name="f[{$field.Key}]" />
                <table>
                <tr>
                {foreach from=$field.Values item='option' key='key' name='radioF'}
                    <td {if $smarty.foreach.radioF.total > 5}style="width: 33%"{/if}>
                        <input type="radio" id="{$field.Key}_{$key}" value="{$key}" name="f[{$field.Key}]" {if $fVal.$fKey}{if $fVal.$fKey == $key}checked="checked"{/if}{else}{if $field.Default == $key}checked="checked"{/if}{/if} /> <label for="{$field.Key}_{$key}" class="fLable">{$lang[$option.pName]}</label>
                    </td>
                    {if $smarty.foreach.radioF.iteration%3 == 0}
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
                    <div id="{$field.Key}_file" style="padding: 0 0 5px 0;">
                        <input type="hidden" name="f[sys_exist_{$field.Key}]" value="{$field_value}" />

                        {if $field.Type == 'file'}
                            <a href="{$smarty.const.RL_FILES_URL}{$field_value}">{$lang.download}</a>
                            |
                            <a id="delete_{$field.Key}" href="javascript:void(0)">{$lang.delete}</a>
                        {else}
                            <div class="relative fleft">
                                <img style="width: auto;height: auto;" class="thumbnail" title="{$field.name}" alt="{$field.name}" src="{$smarty.const.RL_FILES_URL}{$field_value}" />
                                <img id="delete_{$field.Key}" class="delete_item" style="display: block;" src="{$rlTplBase}img/blank.gif" alt="" title="{$lang.delete}" />
                            </div>
                            <div class="clear"></div>
                        {/if}

                        <script type="text/javascript">//<![CDATA[
                        {literal}

                        $(document).ready(function(){
                            $('#delete_{/literal}{$field.Key}{literal}').click(function(){
                                {/literal}
                                rlConfirm('{$lang.delete_confirm}', 'xajax_delAccountFile', Array('{$field.Key}"', '{$aInfo.ID}', '"{$field.Key}_file'));
                                {literal}
                            });
                        });

                        {/literal}
                        //]]>
                        </script>
                    </div>
                {/if}
                {getTmpFile field=$field.Key parent="f"}
                <input type="file" name="{$field.Key}" />{if $field.Type == 'file' && !empty($field.Default)}<span class="grey_small"> <em>{$l_file_types.$field_type.name} (.{$l_file_types.$field_type.ext|replace:',':', .'})</em></span>{/if}
            {elseif $field.Type == 'accept'}
                <textarea cols="" rows="6" readonly="readonly" name="{$field.Key}">{$field.default}</textarea>
                <div style="padding: 5px 0 0 0;">
                    <input type="hidden" name="f[{$field.Key}]" value="no" />
                    <input type="checkbox" id="{$field.Key}" name="f[{$field.Key}]" value="yes" /> <label for="{$field.Key}" class="fLable">{$lang.accept}</label>{if $field.Required}<span class="red">*</span>{/if}
                </div>
            {/if}
        </td>
    </tr>

{/foreach}
</table>

<!-- account fields add end -->
