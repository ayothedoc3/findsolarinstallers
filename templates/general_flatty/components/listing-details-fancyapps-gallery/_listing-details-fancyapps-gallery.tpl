<!-- listing picture fancyapps gallery tpl -->

<div class="gallery">
    {if !$allow_photos}
        <div class="picture_locked hide">
            <div>
                <div class="restricted-content text-center">
                    <img class="locked-media" src="{$rlTplBase}img/blank.gif" />
                    {if $isLogin}
                        <p class="picture-hint hide">{$lang.view_picture_not_available}</p>
                        <p class="video-hint hide">{$lang.watch_video_not_available}</p>
                        <span>
                            <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                        </span>
                    {else}
                        <p class="picture-hint hide">{$lang.view_picture_hint}</p>
                        <p class="video-hint hide">{$lang.watch_video_hint}</p>
                        <span>
                            <a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
                        </span>
                    {/if}
                </div>
            </div>
        </div>
    {/if}

    <div class="thumbs">
        <div class="f-carousel">
            <div class="f-carousel__viewport">
                <div class="f-carousel__track d-flex flex-nowrap flex-md-wrap">
                    {assign var='replace_count' value=`$smarty.ldelim`count`$smarty.rdelim`}
                    {assign var='rest_count' value=$photos|@count}

                    {foreach from=$photos item='photo' name='photosF'}
                    <div class="f-carousel__slide{if $smarty.foreach.photosF.first} active{/if}{if !$allow_photos && !$smarty.foreach.photosF.first} locked{/if}" data-media-type="{$photo.Type}">
                        <div class="f-carousel__wrapper" data-rest-text="{$lang.count_more_pictures|replace:$replace_count:$rest_count|replace:'<br />':' '}">
                            {if ((!$allow_photos && $smarty.foreach.photosF.first) || $allow_photos) && $photo.Type == 'video' && !$photo.video_key}
                                <video><source src="{$photo.Original}" type="video/mp4"></source></video>
                            {else}
                                <img alt="{if $photo.Description}{$photo.Description|escape:'html'}{else}{$pageInfo.name|escape:'html'}{/if}"
                                     src="{if $photo.Thumbnail && ($allow_photos || $smarty.foreach.photosF.first)}{if $smarty.foreach.photosF.first && $photo.Type != 'video'}{$photo.Photo}{else}{$photo.Thumbnail}{/if}{else}{$rlTplBase}img/blank.gif{/if}"
                                     {if $photo.Thumbnail_x2 && ($allow_photos || $smarty.foreach.photosF.first)}srcset="{$photo.Thumbnail_x2} 2x"{/if} />
                            {/if}

                            {if $photo.Type == 'video'}<span class="play"></span>{/if}

                            {if $smarty.foreach.photosF.first}
                                {if $enlargeNav}<span class="media-enlarge cursor-pointer"><span></span></span>{/if}

                                {rlHook name='tplListingDetailsPhotoPreview'}
                            {/if}
                        </div>
                    </div>
                    {math equation='count-1' assign='rest_count' count=$rest_count}
                    {/foreach}
                </div>
            </div>
        </div>
    </div>

    <script>
    let galleryData = [];

    {foreach from=$photos item='photo' name='photosF'}
        {if !$smarty.foreach.photosF.first && !$allow_photos}
            {break}
        {/if}

        galleryData.push({$smarty.ldelim}
            type: '{if $photo.Type == 'video'}{if $photo.video_key}iframe{else}html5video{/if}{else}image{/if}',
            src: '{if $photo.Type == 'video'}{if $photo.video_key}{$photo.href}{else}{$photo.Original}{/if}{else}{$photo.Photo}{/if}',
            thumb: '{if $photo.Thumbnail_x2}{$photo.Thumbnail_x2}{elseif $photo.Thumbnail}{$photo.Thumbnail}{else}{$rlTplBase}img/play.svg{/if}',
            caption: "{if $photo.Description}{$photo.Description|escape:'javascript'}{else}{$pageInfo.name|escape:'javascript'}{/if}",
        {$smarty.rdelim});
    {/foreach}
    </script>
</div>

<script class="fl-js-dynamic">
lang.change_plan = "{$lang.change_plan}";
{literal}

$(function(){
    var gCarousel = false;

    var $gl = $('div.gallery');
    var $items = $gl.find('div.thumbs .f-carousel__slide');

    flUtil.loadStyle([rlConfig['libs_url']+'fancyapps/fancybox.css', rlConfig['libs_url']+'fancyapps/carousel.css']);
    flUtil.loadScript(rlConfig['libs_url']+'fancyapps/fancybox.umd.js', function(){
        $items.click(function(e){
            $slide = $(this);

            if ($(this).hasClass('locked')) {
                flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
                flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
                    if (isLogin) {
                        var content = $('.picture_locked > div').clone(true, true);
                        content.removeClass('hide').addClass('w-100');
                        content.find('.' + $slide.data('media-type') + '-hint').removeClass('hide');
                    } else {
                        var content = $('#login_modal_source > .tmp-dom').clone(true, true);
                    }

                    $('body').popup({
                        caption: isLogin ? lang.change_plan : lang.login,
                        click: false,
                        content: content,
                        width: 320
                    });
                });
            } else {
                Fancybox.show(galleryData, {
                    startIndex: $items.index($(this)),
                    compact: false,
                    contentClick: "iterateZoom",
                    Images: {
                        Panzoom: {
                            maxScale: rlConfig['gallery_max_zoom_level']
                        }
                    },
                    Toolbar: {
                        display: {
                            left: ['infobar'],
                            right: ['reset', 'zoomOut', 'zoomIn', 'slideshow', 'fullscreen', 'thumbs', 'close']
                        }
                    }
                });
            }
        });

        gCarousel = new Carousel(document.querySelector('.f-carousel'), {
            center: true,
            Dots: false,
            enabled: false,
            direction: rlLangDir,
            breakpoints: {
                '(max-width: 767px)': {
                    enabled: true
                },
            },
            on: {
                'detachPlugins': (carousel, eventName) => {
                    $('.f-carousel').addClass('d-flex flex-nowrap flex-md-wrap');
                },
            },
        });
    });
});

{/literal}
</script>

<!-- listing picture fancyapps gallery tpl end -->
