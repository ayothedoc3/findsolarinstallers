<!-- listings box tpl -->

<!-- navigation bar -->
<div id="nav_bar">

    {if $aRights.$cKey.add && $smarty.get.action != 'add'}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.listings_box_add_new_block}</span><span class="right"></span></a>
    {/if}

    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.listings_box_block_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

    {assign var='sPost' value=$smarty.post}

    <!-- add new/edit block -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form onsubmit="return submitHandler();"  action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;block={$smarty.get.block}{/if}" method="post" enctype="multipart/form-data">
            <input type="hidden" name="submit" value="1" />

            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="fromPost" value="1" />
                <input type="hidden" name="id" value="{$sPost.id}" />
            {/if}
            <table class="form">
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.name}
                </td>
                <td>
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}

                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                        <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                        {if $allLangs|@count > 1}
                                <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.block_type}</td>
                <td class="field">
                    <select name="box_type">
                        <option value="">{$lang.select}</option>
                        {foreach from=$box_types item='box_type' key='sKey'}
                        <option value="{$sKey}" {if $sKey == $sPost.box_type}selected="selected"{/if}>{$box_type}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.block_side}</td>
                <td class="field">
                    <select name="side">
                        <option value="">{$lang.select}</option>
                        {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
                            {if $sKey!='integrated_banner'}
                                <option value="{$sKey}" {if $sKey == $sPost.side}selected="selected"{/if}>{$block_side}</option>
                            {/if}
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.use_block_header}</td>
                <td class="field">
                    {if $sPost.header == '1'}
                        {assign var='header_yes' value='checked="checked"'}
                    {elseif $sPost.header == '0'}
                        {assign var='header_no' value='checked="checked"'}
                    {else}
                        {assign var='header_yes' value='checked="checked"'}
                    {/if}
                    <label><input {$header_yes} class="lang_add" type="radio" name="header" value="1" /> {$lang.yes}</label>
                    <label><input {$header_no} class="lang_add" type="radio" name="header" value="0" /> {$lang.no}</label>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.use_block_design}</td>
                <td class="field">
                    {if $sPost.tpl == '1'}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {elseif $sPost.tpl == '0'}
                        {assign var='tpl_no' value='checked="checked"'}
                    {else}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {/if}
                    <label><input {$tpl_yes} class="lang_add" type="radio" name="tpl" value="1" /> {$lang.yes}</label>
                    <label><input {$tpl_no} class="lang_add" type="radio" name="tpl" value="0" /> {$lang.no}</label>
                </td>
            </tr>
            </table>

            {if $config.rl_version|version_compare:"4.10.0" > 0}
            <table class="form">
            <tr>
                <td class="name">{$lang.lb_load_more_button}</td>
                <td class="field">
                    {assign var='checkbox_field' value='load_more'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}

                    <input {$load_more_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$load_more_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>
            </table>
            <div id="view_all_link_cont">
                <table class="form">
                <tr>
                    <td class="name">{$lang.lb_view_all_link}</td>
                    <td class="field">
                        {assign var='checkbox_field' value='view_all_link'}

                        {if $sPost.$checkbox_field == '1'}
                            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                        {elseif $sPost.$checkbox_field == '0'}
                            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                        {else}
                            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                        {/if}

                        <input {$view_all_link_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                        <input {$view_all_link_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                    </td>
                </tr>
                </table>
            </div>

            <script type="text/javascript">
            {literal}

            $(function(){
                var $side = $('[name=side]');
                var $header = $('[name=header]');
                var headerLinkOptionsHandler = function(){
                    $('[id=view_all_link_cont]')[
                        ['top','bottom','middle'].indexOf($side.val()) >= 0 && $header.filter(':checked').val() == '1'
                            ? 'removeClass'
                            : 'addClass'
                    ]('hide');
                }

                headerLinkOptionsHandler();
                $side.change(function(){
                    headerLinkOptionsHandler();
                });
                $header.click(function(){
                    headerLinkOptionsHandler();
                });
            });

            {/literal}
            </script>
            {/if}

            <table class="form">
            <tr>
                <td class="name">{$lang.listings_box_display_mode}</td>
                <td id="display_mode" class="field">

                    {if $sPost.display_mode == 'default'}
                        {assign var='display_mode_yes' value='checked="checked"'}
                    {elseif $sPost.display_mode == 'grid'}
                        {assign var='display_mode_no' value='checked="checked"'}
                    {else}
                        {assign var='display_mode_yes' value='checked="checked"'}
                    {/if}
                    <label><input {$display_mode_yes} class="lang_add" type="radio" name="display_mode" value="default" /> {$lang.listings_box_default}</label>
                    <label><input {$display_mode_no} class="lang_add" type="radio" name="display_mode" value="grid" /> {$lang.listings_box_grid}</label>
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.listings_box_number_of_listing}</td>
                <td class="field">
                    <input type="text" class="numeric" name="count" value="{$sPost.count}" maxlength="2" style="width: 139px;" />
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.listings_box_by_category}</td>
                <td class="field">
                    {if $sPost.by_category == '1'}
                        {assign var='by_category_yes' value='checked="checked"'}
                    {elseif $sPost.unique == '0'}
                        {assign var='by_category_no' value='checked="checked"'}
                    {else}
                        {assign var='by_category_no' value='checked="checked"'}
                    {/if}
                    <label><input {$by_category_yes} class="lang_add" type="radio" name="by_category" value="1" /> {$lang.yes}</label>
                    <label><input {$by_category_no} class="lang_add" type="radio" name="by_category" value="0" /> {$lang.no}</label>
                    <span class="field_description">{$lang.listings_box_by_category_desc}</span>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.listings_box_dublicate}</td>
                <td class="field">
                    {if $sPost.unique == '1'}
                        {assign var='dub_yes' value='checked="checked"'}
                    {elseif $sPost.unique == '0'}
                        {assign var='dub_no' value='checked="checked"'}
                    {else}
                        {assign var='dub_no' value='checked="checked"'}
                    {/if}
                    <label><input {$dub_yes} class="lang_add" type="radio" name="unique" value="1" /> {$lang.yes}</label>
                    <label><input {$dub_no} class="lang_add" type="radio" name="unique" value="0" /> {$lang.no}</label>
                </td>
            </tr>
            <tr>
                <td class="divider_line" colspan="2">
                    <div class="inner">{$lang.listings_box_listings_source}</div>
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.listing_type}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_type" onclick="fieldset_action('type');" class="up">{$lang.listing_type}</legend>
                        <div id="type">
                            <table id="list_rt">
                                <tr>
                                    <td valign="top">
                                    {foreach from=$listing_types item='listing_type' name='typeF'}
                                    {if $listing_type.Photo
                                        || ($listing_type.Key == 'jobs' && $config.package_name == 'general')
                                        || ($listing_type.Key == 'tasks' && $config.package_name == 'service')
                                    }
                                        <div style="padding: 2px 8px;">
                                            <input class="checkbox"
                                                   {if $sPost.type && $listing_type.Type|in_array:$sPost.type}checked="checked"{/if}
                                                   id="type_{$listing_type.Type}"
                                                   type="checkbox"
                                                   name="type[{$listing_type.Type}]"
                                                   value="{$listing_type.Type}" /> <label class="cLabel" for="type_{$listing_type.Type}">{$listing_type.name}</label>
                                        </div>
                                        {assign var='perCol' value=$smarty.foreach.typeF.total/3|ceil}

                                        {if $smarty.foreach.typeF.iteration % $perCol == 0}
                                            </td>
                                            <td valign="top">
                                        {/if}
                                    {/if}
                                    {/foreach}
                                    </td>
                                </tr>
                            </table>
                            <div class="grey_area" style="margin: 0 0 5px;">
                                <span>
                                    <span onclick="checkAll(true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="checkAll(false);" class="green_10">{$lang.uncheck_all}</span>
                                </span>
                            </div>
                        </div>
                    </fieldset>
                </td>
            </tr>
            <tr id="use_category" class="hide">
                <td class="name">{$lang.listings_box_use_category}</td>
                <td class="field">
                    <table><tr><td>
                        {if $sPost.use_category == '1'}
                            {assign var='use_category_yes' value='checked="checked"'}
                        {elseif $sPost.use_category == '0'}
                            {assign var='use_category_no' value='checked="checked"'}
                        {else}
                            {assign var='use_category_no' value='checked="checked"'}
                        {/if}
                        <label><input {$use_category_yes} class="lang_add" type="radio" name="use_category" value="1" /> {$lang.yes}</label>
                        <label><input {$use_category_no} class="lang_add" type="radio" name="use_category" value="0" /> {$lang.no}</label>
                    </td></tr></table>
                </td>
            </tr>
            <tr id="use_categories" class="hide">
                <td class="name">{$lang.categories}</td>
                <td class="field">
                    {include file=$smarty.const.RL_PLUGINS|cat:'listings_box'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'category_box.tpl' mode='cats'}
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.lb_filter_by_field}</td>
                <td class="field">
                    <div id="filter_controls">
                        <select>
                            <option value="">{$lang.select}</option>
                            {foreach from=$filter_fields item='filter_field'}
                            <option value="{$filter_field.Key}">{$filter_field.name} ({phrase key='type_'|cat:$filter_field.Type})</option>
                            {/foreach}
                        </select>
                        <input type="button" value="{$lang.add}" data-phrase="{$lang.add}" style="margin-top: 0;" />
                    </div>

                    <div id="filter_fields" style="margin-top: 15px;">
                        {if $fields_data}
                            {foreach from=$fields_data item='field_data'}
                                {include file=$smarty.const.RL_PLUGINS|cat:'listings_box/admin/field_filter.tpl'}
                            {/foreach}
                        {/if}
                    </div>

                    <script>
                    {literal}

                    $(function(){
                        var $select = $('#filter_controls select');
                        var $button = $('#filter_controls input');
                        var $filterCont = $('#filter_fields');

                        // Add field handler
                        $button.click(function(){
                            var key = $select.val();

                            if (!key) {
                                return;
                            }

                            $select.find('option[value=' + key + ']').prop('disabled', true);
                            $button.val(lang.loading);

                            var data = {
                                mode: 'listingBoxAddField',
                                item: 'listingBoxAddField',
                                lang: rlLang,
                                key: key,
                            }

                            flUtil.ajax(data, function(response, status) {
                                if (status === 'success' && response.status === 'OK') {
                                    $filterCont.append(response.results);
                                } else {
                                    printMessage('error', lang.system_error)
                                }

                                $button.val($button.data('phrase'));
                            }, true);
                        });

                        // Delete field handler
                        $filterCont.on('click', '.delete_item', function(){
                            var $cont = $(this).closest('.options-section');
                            $cont.slideUp(function(){
                                $cont.remove();
                            });

                            var key = $cont.data('key');
                            $select.find('option[value=' + key + ']').prop('disabled', false);
                        });

                        // Disable fields in use
                        $filterCont.find('.options-section').each(function(){
                            var key = $(this).data('key');
                            $select.find('option[value=' + key + ']').prop('disabled', true);
                        });
                    });

                    {/literal}
                    </script>
                </td>
            </tr>
            <tr>
                <td class="divider_line" colspan="2">
                    <div class="inner">{$lang.listings_box_box_appearing}</div>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.show_on_pages}</td>
                <td class="field" id="pages_obj">
                    <fieldset class="light">
                        {assign var='pages_phrase' value='admin_controllers+name+pages'}
                        <legend id="legend_pages" class="up">{$lang.$pages_phrase}</legend>
                        <div id="pages">
                            <div id="pages_cont" {if !empty($sPost.show_on_all)}style="display: none;"{/if}>
                                {assign var='bPages' value=$sPost.pages}
                                <table class="sTable" style="margin-bottom: 15px;">
                                <tr>
                                    <td valign="top">
                                    {foreach from=$pages item='page' name='pagesF'}
                                    {assign var='pId' value=$page.ID}
                                    <div style="padding: 2px 8px;">
                                        <input class="checkbox" {if isset($bPages.$pId)}checked="checked"{/if} id="page_{$page.ID}" type="checkbox" name="pages[{$page.ID}]" value="{$page.ID}" /> <label class="cLabel" for="page_{$page.ID}">{$page.name}</label>
                                    </div>
                                    {assign var='perCol' value=$smarty.foreach.pagesF.total/3|ceil}

                                    {if $smarty.foreach.pagesF.iteration % $perCol == 0}
                                        </td>
                                        <td valign="top">
                                    {/if}
                                    {/foreach}
                                    </td>
                                </tr>
                                </table>
                            </div>

                            <div class="grey_area" style="margin: 0 0 5px;">
                                <label><input id="show_on_all" {if $sPost.show_on_all}checked="checked"{/if} type="checkbox" name="show_on_all" value="true" /> {$lang.sticky}</label>
                                <span id="pages_nav" {if $sPost.show_on_all}class="hide"{/if}>
                                    <span onclick="$('#pages_cont input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#pages_cont input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </span>
                            </div>
                        </div>
                    </fieldset>

                    <script type="text/javascript">
                    {literal}

                    $(document).ready(function(){
                        $('#legend_pages').click(function(){
                            fieldset_action('pages');
                        });

                        $('input#show_on_all').click(function(){
                            $('#pages_cont').slideToggle();
                            $('#pages_nav').fadeToggle();
                        });
                    });

                    {/literal}
                    </script>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.show_in_categories}</td>
                <td class="field">
                    {include file=$smarty.const.RL_PLUGINS|cat:'listings_box'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'category_box.tpl' mode='categories' check_all=true}

                </td>
            </tr>

            {rlHook name='apTplBlocksForm'}
            <tr>
                <td class="name"><span class="red">*</span>{$lang.status}</td>
                <td class="field">
                    <select name="status">
                        <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
                </td>
            </tr>
            </table>
        </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add new block end -->

    <!-- select category action -->
    <script type="text/javascript">

    {literal}

        var flynaxCustomTree = function () {
            var self = this;
            var selected = [];
            var parents = [];
            this.init = function(mode, catsSelected, parentsCats){
                selected[mode] = catsSelected;
                parents[mode] = parentsCats;
                self.callAction(mode);
                self.openCatTree(0, mode);
            }
            this.callAction = function(modeTmp){
                $('body').on('click', '#categories_'+modeTmp +' .tree .tree_cat >img:not(.no_child)', function(e){

                    var $obj = $(this);
                    var id = $obj.parent().data('index');
                    mode = $obj.closest('.tree').data('key');
                    
                    if ( $obj.hasClass('opened') )
                    {
                        $obj.removeClass('opened');
                        $obj.parent().find('ul:first').hide();
                    }
                    else
                    {
                        $obj.addClass('opened');            
                        $obj.parent().find('ul:first').show();
                    }

                    if (!$obj.hasClass('done')) {
                        self.loadCats($obj, id, modeTmp);
                    }
                });
            }

            this.loadCats = function($obj, id, mode){
                var data = {
                    mode: 'listingBoxCatTree',
                    item: 'listingBoxCatTree',
                    lang: rlLang,
                    id: id,
                    input_mode: mode,
                }

                flUtil.ajax(data, function(response, status) {
                    if (status === 'success' && response.status === 'ok') {
                        var $content = $obj.parent();
                        $content.append(response.data);
                        $obj.addClass('done');

                        self.openCatTree(id, mode);
                    }
                }, true);
            }

            this.openCatTree = function(parent_id, mode){

                var main_id = 'categories_'+mode;
                if (parent_id == 0) {
                    main_id += ' .tree ul.first';
                }
                else {
                    main_id += ' .tree .tree_cat[data-index='+parent_id+']';
                }


                if (parents[mode]) {
                    for (var i=0; i<parents[mode].length; i++) {
                        var idx = parents[mode][i];
                        var $content = $('#'+main_id + ' .tree_cat[data-index='+idx+']');
                        if ($content.length > 0 && !$content.hasClass('done')) {
                            $content.find('>img').trigger('click')
                            $content.addClass('done');
                        }
                    }
                }

                if (selected[mode][0]) {
                    for (var i=0; i<selected[mode].length; i++) {
                        var $content = $('#'+main_id);
                        var $current = $content.find('.tree_cat[data-index='+selected[mode][i]+'] >label>input');
                        $current.prop('checked', true);
                    }
                }
            };
        }
        var customTree = new flynaxCustomTree();

        $('#type input').on('change', function() {
            _onLoadCustomCats();
        });

        $('input[name=use_category]').click(function(){
           _showCustomCats();
        });

        var checkAll = function(mode){
            $('#list_rt input').prop('checked', mode);
            _onLoadCustomCats();
        }

        var _onLoadCustomCats = function(){
            var count = $("#type input:checked").length;
            if (count == 1) {                
                $("#use_category").removeClass('hide');
            }
            else {
                $("#use_category").addClass('hide');
            }
            _showCustomCats();
        }

        var _showCustomCats = function(){
            if($('input[name=use_category]:checked').val() == 1 && $("#type input:checked").length == 1) {
                $('#use_categories').show();
                $('#categories_cats input').prop('checked', false);
                var cat = $("#type input:checked").val();
                $('#use_categories .tree > *').hide();
                $('#use_categories .tree').find('[data-key='+cat+']').show();
            }
            else {
                $('#use_categories').hide();
            }
        }

        $('input[name=cat_sticky]').click(function(){
            $('#categories_categories .tree').slideToggle();
            $('#cats_nav').fadeToggle();
        });
        _onLoadCustomCats();


    function cat_chooser(cat_id){
        return true;
    }
    {/literal}

    {if $smarty.post.parent_id}
        cat_chooser('{$smarty.post.parent_id}');
    {elseif $smarty.get.parent_id}
        cat_chooser('{$smarty.get.parent_id}');
    {/if}
    </script>
    <!-- select category action end -->

    <!-- additional JS -->
    {if $sPost.type}
    <script type="text/javascript">
    {literal}
    $(document).ready(function(){
        block_banner('btype_{/literal}{$sPost.type}{literal}', '#btypes div');
    });

    {/literal}
    </script>
    {/if}
    <!-- additional JS end -->

{else}
    <script type="text/javascript">
    // blocks sides list
    var block_sides = [
    {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
        {if $sKey!='integrated_banner'}
            ['{$sKey}', '{$block_side}']{if !$smarty.foreach.sides_f.last},{/if}
        {/if}
    {/foreach}
    ];

    // blocks box types list
    var block_types = [
    {foreach from=$box_types item='block_types' name='sides_f' key='sKey'}
        ['{$sKey}', '{$block_types}']{if !$smarty.foreach.sides_f.last},{/if}
    {/foreach}
    ];

    </script>
    <div id="gridListingsBox"></div>
    <script type="text/javascript">//<![CDATA[
    lang['listings_box_number_of_listing'] = '{$lang.listings_box_number_of_listing}'
    var listingsBox;

    {literal}
    $(document).ready(function(){

        listingsBox = new gridObj({
            key: 'listings_box',
            id: 'gridListingsBox',
            ajaxUrl: rlPlugins + 'listings_box/admin/listings_box.inc.php?q=ext',
            defaultSortField: 'ID',
            title: lang['ext_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'name', mapping: 'name'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Box_type', mapping: 'Box_type'},
                {name: 'Count', mapping: 'Count'},
                {name: 'Side', mapping: 'Side'},
                {name: 'Status', mapping: 'Status'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    fixed: true,
                    width: 40
                },{
                    header: lang['ext_name'],
                    dataIndex: 'name'
                },{
                    header: lang['listings_box_ext_box_type'],
                    dataIndex: 'Box_type',
                    width: 120,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: block_types,
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: false,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['listings_box_number_of_listing'],
                    dataIndex: 'Count',
                    width: 120,
                    fixed: true,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        maxValue: 30,
                        minValue: 1
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_block_side'],
                    dataIndex: 'Side',
                    width: 120,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: block_sides,
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang['ext_active']],
                            ['approval', lang['ext_approval']]
                        ],
                        mode: 'local',
                        typeAhead: true,
                        triggerAction: 'all',
                        selectOnFocus: true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(id) {
                        var out = '';

                        // edit
                        out += '<a href="' + rlUrlHome + 'index.php?controller='+controller+'&action=edit&block='+id+'">';
                        out += '<img class="edit ext:qtip="' + lang['ext_edit'] + '" src="' + rlUrlHome + 'img/blank.gif" /></a>';

                        // delete
                        out += '<img data-id="'+id+'" class="remove" ext:qtip="' + lang['ext_delete'] + '"';
                        out += 'src="' + rlUrlHome + 'img/blank.gif"  />';

                        return out;
                    }
                }
            ]
        });

        listingsBox.init();
        grid.push(listingsBox.grid);

        $('#gridListingsBox').on('click', 'img.remove', deleteListingsBox.confirm)

    });

    var deleteListingsBoxClass = function(){

        this.confirm = function() {
            var id = $(this).data("id");
            rlConfirm(lang['ext_notice_delete'], "deleteListingsBox.request", id);
        }

        this.request = function(index) {
            $.get(rlConfig["ajax_url"], {item: 'deleteListingsBox', id: index}, function (response) {
                printMessage('notice', response.message);
                listingsBox.init();
            }, 'json');
        }
    }

    var deleteListingsBox = new deleteListingsBoxClass();

    {/literal}
    //]]>
    </script>
{/if}
<!-- listings box tpl end -->
