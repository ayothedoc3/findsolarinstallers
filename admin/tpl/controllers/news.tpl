<!-- news tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplNewsNavBar'}

    {if !$smarty.get.mode || ($smarty.get.mode === 'categories' && $smarty.get.action)}
        <a href="{$rlBaseC}mode=categories" class="button_bar">
            <span class="center_list">{$lang.categories}</span>
        </a>
    {/if}
    {if $aRights.$cKey.add && $smarty.get.mode === 'categories' && !$smarty.get.action}
        <a href="{$rlBaseC}mode=categories&action=add" class="button_bar">
            <span class="center-add">{$lang.add_category}</span>
        </a>
    {/if}

    {if $aRights.$cKey.add && !$smarty.get.mode}
        <a href="{$rlBaseC}action=add" class="button_bar">
            <span class="center-add">{$lang.add_news}</span>
        </a>
    {/if}
    {if !$smarty.get.mode || $smarty.get.mode === 'categories'}
        <a href="{$rlBaseC}" class="button_bar">
            <span class="center_list">{$lang.news_list}</span>
        </a>
    {/if}
</div>
<!-- navigation bar end -->

{if $smarty.get.action}
    {assign var='sPost' value=$smarty.post}

    {if $smarty.get.mode === 'categories'}
        <!-- add new category for news -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form onsubmit="return submitHandler()"
            action="{$rlBaseC}mode=categories&action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;id={$smarty.get.id}{/if}"
            method="post"
            enctype="multipart/form-data"
        >
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.name}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.title}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="title[{$language.Code}]" value="{$sPost.title[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.h1_heading}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="h1_heading[{$language.Code}]" value="{$sPost.h1_heading[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.h1_heading} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.meta_description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {assign var='lMetaDescription' value=$sPost.meta_description}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="50" rows="2" name="meta_description[{$language.Code}]">{$lMetaDescription[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.url}</td>
            <td class="field">
                <table>
                <tr>
                    <td><span style="padding: 0 5px 0 0;" class="field_description_noicon">{$smarty.const.RL_URL_HOME}{$pages.news}/</span></td>
                    <td><input name="path" type="text" value="{$sPost.path}" maxlength="40" /></td>
                    <td><span class="field_description_noicon">/</span></td>
                </tr>
                </table>
            </td>
        </tr>

        {rlHook name='apTplNewsCategoryNavForm'}

        <tr>
            <td class="name">{$lang.status}</td>
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
        <!-- add new category for news end -->
    {else}
        <!-- add new news -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form onsubmit="return submitHandler()"
            action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;news={$smarty.get.news}{/if}"
            method="post"
            enctype="multipart/form-data"
        >
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        <table class="form">
        {* @since 4.9.3 - Added an ability to select news category *}
        <tr>
            <td class="name">{$lang.category}</td>
            <td class="field">
                <select name="category">
                    <option value="">{$lang.select}</option>
                    {foreach from=$news_categories item='category'}
                        <option value="{$category.Key}" {if $category.Key == $sPost.category}selected="selected"{/if}>{$category.Name}</option>
                    {/foreach}
            </select>
            </td>
        </tr>
        {* @since 4.9.3 - Added an ability to upload a picture to the news *}
        <tr>
            <td class="name">{$lang.photo}</td>
            <td class="field_tall">
                <input class="file" type="file" name="picture" />

                {if $news_info.Picture}
                    <div style="padding: 15px 0;">
                        <img style="max-width: 200px;max-height: 200px;" src="{$smarty.const.RL_FILES_URL}news/{$news_info.Picture}" />
                    </div>
                {/if}
            </td>
        </tr>
        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.title}
            </td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.meta_description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="" rows="" name="meta_description[{$language.Code}]">{$sPost.meta_description[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.content}
            </td>
            <td class="field ckeditor">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    {assign var='dCode' value='content_'|cat:$language.Code}
                    {fckEditor name='content_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name"><span class="red">*</span>{$lang.page_url}</td>
            <td class="field">
                <table>
                <tr>
                    <td>
                        <span style="padding: 0 5px 0 0;" class="field_description_noicon category-url">
                            {$smarty.const.RL_URL_HOME}{$pages.news}/
                        </span>
                    </td>
                    <td>
                        <input name="path" type="text" value="{$sPost.path}" maxlength="40" />
                    </td>
                    <td>
                        <span class="field_description_noicon">
                            {if $config.html_in_pages}.html{else}/{/if}
                        </span>
                    </td>
                </tr>
                </table>
            </td>
        </tr>

        {if $smarty.get.action == 'edit'}
        <tr>
            <td class="name"><span class="red">*</span>{$lang.date}</td>
            <td class="field">
                <input class="date" name="date" type="text" value="{$sPost.date}" style="width: 120px;" maxlength="40" />
            </td>
        </tr>
        {/if}

        {rlHook name='apTplNewsNavForm'}

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

        <script>
        let newsCategories = [], newsPagePath = '{$pages.news}';

        {foreach from=$news_categories item='category'}
            newsCategories[{$category.ID|intval}] = '{$category.Path}';
        {/foreach}

        {literal}
        $(function () {
            newsPreviewUrlHandler();

            $('select[name="category"]').change(function () {
                newsPreviewUrlHandler();
            });

            /**
             * Update preview of news URL by selected category
             */
            function newsPreviewUrlHandler () {
                let $category = $('select[name="category"] option:selected');
                let categoryID = $category.val() ? Number($category.val()) : 0, categoryURL;

                categoryURL = rlConfig.frontendURL + newsPagePath + '/';

                if (newsCategories && categoryID && newsCategories[categoryID]) {
                    categoryURL += newsCategories[categoryID] + '/';
                }

                $('.category-url').text(categoryURL);
            }
        });
        {/literal}</script>
        <!-- add new news end -->
    {/if}
{else}
    {if $smarty.get.mode === 'categories'}
        <!-- News categories grid -->
        <div id="grid"></div>
        <script type="text/javascript">
        var newsCategoriesGrid;

        {literal}
        $(function() {
            newsCategoriesGrid = new gridObj({
                key             : 'news_categories',
                id              : 'grid',
                ajaxUrl         : `${rlUrlHome}controllers/news.inc.php?q=ext_categories`,
                defaultSortField: 'ID',
                defaultSortType : 'DESC',
                title           : lang.ext_categories_manager,
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'Name', mapping: 'Name'},
                    {name: 'Status', mapping: 'Status'},
                ],
                columns: [
                    {
                        header: lang.ext_id,
                        dataIndex: 'ID',
                        width: 40,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    },{
                        header: lang.ext_name,
                        dataIndex: 'Name',
                        width: 60,
                    },{
                        header: lang.ext_status,
                        dataIndex: 'Status',
                        width: 12,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang.active],
                                ['approval', lang.approval]
                            ],
                            displayField : 'value',
                            valueField   : 'key',
                            typeAhead    : true,
                            mode         : 'local',
                            triggerAction: 'all',
                            selectOnFocus: true
                        })
                    },{
                        header: lang.ext_actions,
                        width: 70,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(data) {
                            let out = '<center>';

                            if (rights[cKey].indexOf('edit') >= 0) {
                                let imgEdit = `<img class="edit" ext:qtip="${lang.ext_edit}" src="${rlUrlHome}img/blank.gif">`;
                                out += `<a href="${rlUrlController}&mode=categories&action=edit&id=${data}">${imgEdit}</a>`;
                            }
                            if (rights[cKey].indexOf('delete') >= 0) {
                                out += `<img class="remove"
                                             ext:qtip="${lang.ext_delete}"
                                             src="${rlUrlHome}img/blank.gif"
                                             onclick="rlConfirm('${lang['ext_notice_' + delete_mod]}', 'deleteNewsCategory', '${[data]}', 'news_load')" />`;
                            }
                            out += '</center>';

                            return out;
                        }
                    }
                ]
            });

            {/literal}{rlHook name='apTplNewsCategoriesNavGrid'}{literal}

            newsCategoriesGrid.init();
            grid.push(newsCategoriesGrid.grid);
        });

        /**
         * Delete news category
         *
         * @param {int} $id
        */
        var deleteNewsCategory = function(id) {
            flynax.sendAjaxRequest('removeNewsCategory', {id: id}, function(response) {
                if (response.status === 'OK') {
                    newsCategoriesGrid.reload();
                    printMessage('notice', response.message);
                } else {
                    printMessage('error', response.message ? response.message : lang.system_error);
                }
            });
        };
        {/literal}
        </script>
        <!-- News categories grid end -->

        {rlHook name='apTplNewsCategoriesNavBottom'}
    {else}
        <!-- news grid -->
        <div id="grid"></div>
        <script type="text/javascript">//<![CDATA[
        var newsGrid;

        {literal}
        $(document).ready(function(){
            var expanderTpl = '<div style="margin: 0 0px 5px 44px"><img style="max-width: 200px;max-height: 100px;" src="{src}" /></div>';

            newsGrid = new gridObj({
                key: 'news',
                id: 'grid',
                ajaxUrl: `${rlUrlHome}controllers/news.inc.php?q=ext`,
                defaultSortField: 'Date',
                defaultSortType: 'DESC',
                title: lang.ext_news_manager,
                expander: true,
                expanderTpl: expanderTpl,
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'Category', mapping: 'Category'},
                    {name: 'title', mapping: 'title'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'src', mapping: 'src', type: 'string'},
                    {name: 'Views', mapping: 'Views'},
                ],
                columns: [
                    {
                        header: lang.ext_id,
                        dataIndex: 'ID',
                        width: 40,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    },{
                        header: lang.ext_title,
                        dataIndex: 'title',
                        width: 60,
                        id: 'rlExt_item_bold'
                    },{
                        header: lang.ext_category,
                        dataIndex: 'Category',
                        width: 15,
                    },{
                        header: lang.ext_add_date,
                        dataIndex: 'Date',
                        width: 15,
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M')),
                        editor: new Ext.form.DateField({
                            format: 'Y-m-d H:i:s'
                        })
                    },{
                        header: lang.shows,
                        dataIndex: 'Views',
                        width: 8,
                    },{
                        header: lang.ext_status,
                        dataIndex: 'Status',
                        width: 12,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang.active],
                                ['approval', lang.approval]
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus:true
                        })
                    },{
                        header: lang.ext_actions,
                        width: 70,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(data) {
                            var out = '<center>';

                            if (rights[cKey].indexOf('edit') >= 0) {
                                let imgEdit = `<img class="edit" ext:qtip="${lang.ext_edit}" src="${rlUrlHome}img/blank.gif">`;
                                out += `<a href="${rlUrlController}&action=edit&news=${data}">${imgEdit}</a>`;
                            }
                            if (rights[cKey].indexOf('delete') >= 0) {
                                out += `<img class="remove"
                                             ext:qtip="${lang.ext_delete}"
                                             src="${rlUrlHome}img/blank.gif"
                                             onclick="rlConfirm('${lang['ext_notice_' + delete_mod]}', 'deleteNews', '${[data]}', 'news_load')" />`;
                            }
                            out += '</center>';

                            return out;
                        }
                    }
                ]
            });

            {/literal}{rlHook name='apTplNewsNavGrid'}{literal}

            newsGrid.init();
            grid.push(newsGrid.grid);
        });

        /**
         * Delete news
         *
         * @since 4.9.3
         *
         * @param {int} $id
        */
        var deleteNews = function(id) {
            flynax.sendAjaxRequest('removeNews', {id: id}, function(response) {
                if (response.status === 'OK') {
                    newsGrid.reload();
                    printMessage('notice', response.message);
                } else {
                    printMessage('error', response.message ? response.message : lang.system_error);
                }
            });
        };
        {/literal}
        //]]>
        </script>
        <!-- news grid end -->

        {rlHook name='apTplNewsNavBottom'}
    {/if}
{/if}

<!-- news tpl end -->
