<!-- icon manager -->

{assign var='icon_dir' value=$smarty.const.RL_LIBS_URL|cat:'icons/svg-line-set/'}

<tr>
    <td class="name">{if $tpl_settings.listing_type_form_icon}{$lang.use_icon_in_form}{else}{$lang.use_icon_in_menu}{/if}</td>
    <td class="field icon-manager-icon" style="padding-top: 10px">
        <div>
            {if $sPost.category_menu == '1'}
                {assign var='category_menu_yes' value='checked="checked"'}
            {elseif $sPost.category_menu == '0'}
                {assign var='category_menu_no' value='checked="checked"'}
            {else}
                {assign var='category_menu_no' value='checked="checked"'}
            {/if}
            <label><input {$category_menu_yes} type="radio" name="category_menu" value="1" /> {$lang.yes}</label>
            <label><input {$category_menu_no} type="radio" name="category_menu" value="0" /> {$lang.no}</label>
        </div>

        <input type="hidden" name="category_menu_icon" value="{$sPost.category_menu_icon}" />

        <div class="svg-icon-row">
            {$lang.svg_icon_file}:
            {if $sPost.category_menu_icon}
                <img class="img-preview" src="{$icon_dir|cat:$sPost.category_menu_icon}" />
            {/if}
            <a class="icon-set" href="javascript://">{$lang.manage}</a>
            <span class="icon-reset-cont hide">
                / <a class="icon-reset" href="javascript://">{$lang.reset}</a>
            </span>
        </div>
    </td>
</tr>

<script>
lang['search'] = "{$lang.search}";
lang['choose'] = "{$lang.choose}";
lang['cancel'] = "{$lang.cancel}";
rlConfig['url_home'] = "{$smarty.const.RL_URL_HOME}";
rlConfig['template'] = "{$config.template}";
{literal}

$(function(){
    var icons_dir = {/literal}'{$icon_dir}'{literal};
    var $svg_cont = $('.icon-manager-icon .svg-icon-row');
    var $manage   = $svg_cont.find('a.icon-set');
    var $reset    = $svg_cont.find('a.icon-reset');
    var $input    = $('input[name=category_menu_icon]');

    $manage.flModal({
        width: 789,
        height: 'auto',
        caption: lang['svg_icon_file'],
        content: '<div class=""><input name="icon-search" type="text" placeholder="' + lang['search'] + '" /><div id="icons-manager-grid"><div>' + lang['loading'] + '</div></div><div class="icons-manager-next invisible"><input type="button" value="' + lang['load_more'] + '" /></div><div class="icons-manager-controls"><a class="cancel" href="javascript://">' + lang['cancel'] + '</a><input disabled="disabled" type="button" value="' + lang['choose'] + '" /></div></div>',
        onReady: function(){
            var $grid = $('#icons-manager-grid > div');
            var $controls = $('.icons-manager-controls');
            var $next_cont = $('.icons-manager-next');
            var $search = $('input[name=icon-search]');
            var $next = $next_cont.find('input');
            var $choose = $controls.find('input');

            var stack = 0;
            var search_timer = 0;
            var search_query = '';
            var closeWindow = function(){
                $('.modal-window > div:first > span:last').trigger('click');
            }

            $grid.on('click', '.icons-manager-grid-icon', function(){
                $('#icons-manager-grid').find('.icon-active').removeClass('icon-active');
                $(this).addClass('icon-active');
                $choose.attr('disabled', false);
            });
            $controls.find('.cancel').click(function(){
                closeWindow();
            });
            $choose.click(function(){
                var $active_icon = $grid.find('.icon-active');

                if (!$active_icon.data('name')) {
                    return;
                }

                $input.val($active_icon.data('name'));

                if ($svg_cont.find('img.img-preview').length) {
                    $svg_cont.find('img.img-preview').attr(
                        'src',
                        $active_icon.find('img').attr('src')
                    );
                } else {
                    $svg_cont.find('a.icon-set').before(
                        $('<img>')
                            .attr('src', $active_icon.find('img').attr('src'))
                            .addClass('img-preview')
                    );
                }

                $('input[name=category_menu][value=1]').trigger('click');

                closeWindow();
            });

            $next.click(function(){
                stack++;
                loadStack();
                $next.val(lang['loading']);
            });

            $search.on('keyup', function(){
                clearTimeout(search_timer);

                stack = 0;
                search_query = $search.val().length < 3 ? '' : $search.val();

                search_timer = setTimeout(function(){
                    loadStack();
                }, 700);
            });

            var loadStack = function(){
                var data = {
                    start: stack,
                    q: search_query
                };
                flynax.sendAjaxRequest('getSVGIcons', data, function(response){
                    if (!stack) {
                        $grid.empty();
                    }

                    if (response == 'session_expired') {
                        location.reload();
                    } else if (response.results) {
                        $.each(response.results, function(index, icon_name){
                            if (/\.svg$/.test(icon_name)) {
                                var src = icons_dir + icon_name;
                                var class_name = $input.val() == icon_name ? 'icon-active' : '';
                                var $icon = '<div class="icons-manager-grid-icon ' + class_name + '" data-name="' + icon_name + '" title="' + icon_name.replace('.svg', '') + '"><img src="' + src + '" /></div>';
                                $grid.append($icon);
                            }
                        });

                        $next_cont[
                            response.next ? 'removeClass' : 'addClass'
                        ]('invisible');

                        if (stack) {
                            $next.val(lang['load_more']);
                            $grid.parent().animate({scrollTop: $grid.height()}, 'slow');
                        }
                    } else {
                        $grid.append(lang['system_error']);
                    }
                });
            };

            loadStack();
        }
    });

    $reset.click(function(){
        $input.val('');
        $svg_cont.find('img.img-preview').remove();
    });
});

{/literal}
</script>

<!-- icon manager end -->
