    {include file=$componentDir|cat:'call-owner/_floating-buttons.tpl'}

    {displayCSS mode='footer'}

    {displayJS}

    {include file=$componentDir|cat:'call-owner/_popup-interface.tpl'}
    {include file=$componentDir|cat:'contact-owner/_contact-owner.tpl'}

    <script>
    lang.login = "{phrase key='login'}";

    {literal}
        $(function () {
            flUtil.loadScript(rlConfig.tpl_base + 'js/form.js', function () {
                $('select.select-autocomplete').each(function () {
                    flForm.addAutocompleteForDropdown($(this));
                });

                $('.show-phone a').click(function () {
                    let $phone = $(this).parent().parent().find('.hidden-phone');
                    flForm.showHiddenPhone($phone, $phone.data('entity-id'), $phone.data('entity'), $phone.data('field'));
                });

                $('.hidden-phone .messenger-icons a').click(function () {
                    let $messengerLink = $(this);
                    let $showPhoneLink = $(this).closest('.hidden-phone').next('.show-phone').find('a');

                    if ($messengerLink.attr('href') !== 'javascript://') {
                        return;
                    }

                    if ($showPhoneLink.length) {
                        $showPhoneLink.trigger('click');
                    }

                    $messengerLink.attr('data-callback', 'open');
                })
            });

            flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
            flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
                $('a.login').popup({
                    caption: lang.login,
                    content: $('#login_modal_source > .tmp-dom').clone(true, true),
                    width: 320
                });
            });
        });
    {/literal}</script>

    {if $plugins.massmailer_newsletter && false !== $tpl_settings.name|strpos:'_nova_'}
        <script>{literal}
            (function(){
                $('#nova-newsletter-cont').append($('#tmp-newsletter > div'));
                $('#nova-newsletter-cont .newsletter_name').val('Guest');
            })();
        {/literal}</script>
    {/if}

    {if $tpl_settings.listing_picture_slider}
        <script>{literal}
        (function(){
            $('#main_container').on('mouseover', '.listing-picture-slider', function(event){
                if ('ontouchstart' in window
                    || navigator.maxTouchPoints > 0
                    || navigator.msMaxTouchPoints > 0
                ) {
                    return;
                }

                if (!this.sliderPicturesLoaded) {
                    var id = $(this).data('id');
                    var item = this;
                    var counter = 0;

                    var data = {
                        mode: 'getListingPhotos',
                        id: id
                    };
                    flUtil.ajax(data, function(response, status){
                        if (status == 'success') {
                            if (response.status == 'OK') {
                                for (var i in response.data) {
                                    if (i === '0') {
                                        continue;
                                    }

                                    var index = parseInt(i) + 1;
                                    var src = response.data[i].Thumbnail;

                                    $(item).find('.pic-empty-' + index).attr('src', src);
                                }

                                $(item).find('img').one('load', function(){
                                    counter++;

                                    if (counter == (response.data.length - 1)) {
                                        $(item).addClass('listing-picture-slider_loaded');
                                    }
                                });
                            }
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                    }, true);

                    item.sliderPicturesLoaded = true;
                }
            });
        })();
        {/literal}</script>
    {/if}

    <script>
    {literal}

    (function(){
        $('.map-capture img').each(function(){
            var width = $(this).width();
            var height = $(this).height();
            var srcAttr = window.devicePixelRatio === 1 ? 'src' : 'srcset';

            var src = $(this).attr(srcAttr);

            if (src && width && height) {
                src = decodeURIComponent(src);

                if (rlConfig['static_map_provider'] == 'yandex') {
                    src = src.replace(/size=[0-9]+\,[0-9]+/, 'size=' + width + ',' + height);
                } else {
                    src = src.replace(/[0-9]+x[0-9]+/, width + 'x' + height);
                }

                $(this).attr(srcAttr, src);
            }
        });
    })();

    {/literal}
    </script>
</body>
</html>
