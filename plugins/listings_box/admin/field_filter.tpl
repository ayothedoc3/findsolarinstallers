<!-- field filter tpl -->

<div class="options-section" data-key="{$field_data.Key}" style="display: block;">
    <div class="option" style="padding: 10px;">
        <div class="controls" style="padding: 0;">
            <a href="javascript://" class="delete_item">{$lang.remove}</a>
        </div>
        <div class="data">
            <div style="margin-bottom: 8px;">{$field_data.name} ({$field_data.type_name})</div>
            <div>
                {assign var='post_field' value=$sPost.fields[$field_data.Key]}

                {if $field_data.Type == 'select' || $field_data.Type == 'radio'}
                    <select name="fields[{$field_data.Key}]">
                        {foreach from=$field_data.Values item='value'}
                        <option value="{$value.Key}"{if $post_field == $value.Key} selected="selected"{/if}>{if $value.name}{$value.name}{else}{phrase key=$value.pName}{/if}</option>
                        {/foreach}
                    </select>
                {elseif $field_data.Type == 'bool'}
                    <table><tr><td>
                        <label><input type="radio" name="fields[{$field_data.Key}]" value="1"{if !$post_field || $post_field == '1'} checked="checked"{/if}> {$lang.yes}</label>
                        <label><input type="radio" name="fields[{$field_data.Key}]" value="0"{if $post_field == '0'} checked="checked"{/if}> {$lang.no}</label>
                    </td></tr></table>
                {elseif $field_data.Type == 'number'}
                    <select name="fields[{$field_data.Key}][cond]">
                        {foreach from=$number_cond item='cond_name' key='cond_key'}
                        <option value="{$cond_key}"{if $post_field.cond == $cond_key} selected="selected"{/if}>{$cond_name}</option>
                        {/foreach}
                    </select>

                    <input type="text" name="fields[{$field_data.Key}][number]" value="{$post_field.number}" class="numeric w130" placeholder="{$lang.lb_number}" size="10" />
                {elseif $field_data.Type == 'checkbox'}
                    <table><tr><td>
                        <input type="hidden" name="fields[{$field_data.Key}][]" value="" />
                        {foreach from=$field_data.Values item='value'}
                        <label style="width: 190px;"><input type="checkbox" name="fields[{$field_data.Key}][]" value="{$value.Key}"{if $post_field && $value.Key|in_array:$post_field} checked="checked"{/if}> {if $value.name}{$value.name}{else}{phrase key=$value.pName}{/if}</label>
                        {/foreach}
                    </td></tr></table>
                {/if}
            </div>
        </div>
    </div>
</div>

<!-- field filter tpl end -->
