<!-- listing picture gallery tpl -->

<div class="gallery position-relative">
    <div id="media" data-view="gallery">
        <div class="preview{if $photos.0.Type == 'video'} {if $photos.0.video_key}video{else}local{/if}{/if}">
            <iframe id="yt_iframe" frameBorder="0" allow="autoplay"></iframe>

            {if $photos.0.Type == 'video' && !$photos.0.video_key}
            <video id="player" controls>
                <source src="{$photos.0.Original}" type="video/mp4"></source>
            </video>
            {/if}

            <img class="default-picture"
                 title="{if $photos.0.Description}{$photos.0.Description|escape:'html'}{else}{$pageInfo.name|escape:'html'}{/if}"
                 src="{if $photos.0.Photo}{$photos.0.Photo}{else}{$rlTplBase}img/blank.gif{/if}" />

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

            {rlHook name='tplListingDetailsPhotoPreview'}
        </div>

        <div class="map-container"></div>

        {if $noNavBar}
            <span class="media-enlarge"><span></span></span>
        {else}
            <div class="nav-buttons">
                <span class="nav-button zoom">{$lang.view_larger}</span>
                <span class="map-group">
                    {rlHook name='tplListingDetailsMapButtons'}
                    <span class="nav-button gallery">{$lang.gallery}</span>
                    {if $config.map_module && $location.direct}
                        <span class="nav-button map">{$lang.map}</span>
                    {/if}
                </span>
            </div>
        {/if}
    </div>

    <div class="thumbs{if $photos|@count == 1} hide{/if}">
        <div class="f-carousel">
            <div class="f-carousel__viewport">
                <div class="f-carousel__track d-flex">
                    {foreach from=$photos item='photo' name='photosF'}
                    <div class="f-carousel__slide{if $smarty.foreach.photosF.first} active{/if}{if !$allow_photos && !$smarty.foreach.photosF.first} locked{/if}" data-media-type="{$photo.Type}">
                        {if ((!$allow_photos && $smarty.foreach.photosF.first) || $allow_photos) && $photo.Type == 'video' && !$photo.video_key}
                            <video><source src="{$photo.Original}" type="video/mp4"></source></video>
                        {else}
                            <img alt="{if $photo.Description}{$photo.Description|escape:'html'}{else}{$pageInfo.name|escape:'html'}{/if}"
                                 src="{if $photo.Thumbnail && ($allow_photos || $smarty.foreach.photosF.first)}{$photo.Thumbnail}{else}{$rlTplBase}img/blank.gif{/if}"
                                 {if $photo.Thumbnail_x2 && ($allow_photos || $smarty.foreach.photosF.first)}srcset="{$photo.Thumbnail_x2} 2x"{/if} />
                        {/if}

                        {if $photo.Type == 'video'}<span class="play"></span>{/if}
                    </div>
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
            videoType: "{if $photo.Type == 'video' && $photo.video_key}{$photo.Original}{/if}",
        {$smarty.rdelim});
    {/foreach}
    </script>
</div>

<script class="fl-js-dynamic">
lang.change_plan = "{$lang.change_plan}";
rlConfig.gallery_slideshow = {if $config.gallery_slideshow && $allow_photos}true{else}false{/if};
rlConfig.gallery_slideshow_delay = {if $config.gallery_slideshow_delay}{$config.gallery_slideshow_delay}{else}5{/if};
{literal}

$(function(){
    var index = 0;
    var slideshowTimer = false;
    var gCarousel = false;
    var mapLoaded = false;

    var $gl = $('div.gallery');
    var $media = $('#media');
    var $preview = $('.listing-details .gallery div.preview');
    var $enlarge = $gl.find('.media-enlarge,.nav-button.zoom');
    var $previewPic = $preview.find('.default-picture');
    var $thumbs = $gl.find('div.thumbs');
    var $items = $thumbs.find('.f-carousel__slide');
    var $navButtons = $gl.find('.nav-buttons .map-group .nav-button');
    var $player = $('#player');
    var $ytPlayer = $('#yt_iframe');

    flUtil.loadStyle([rlConfig['libs_url']+'fancyapps/fancybox.css', rlConfig['libs_url']+'fancyapps/carousel.css']);
    flUtil.loadScript(rlConfig['libs_url']+'fancyapps/fancybox.umd.js', function(){
        $previewPic.click(function(){
            if (!galleryData[index] || galleryData[index].type != 'image') {
                return;
            }

            clearInterval(slideshowTimer);

            openNext();
        });

        $player.on('play', function(){
            clearInterval(slideshowTimer);
        })

        $enlarge.click(function(){
            clearInterval(slideshowTimer);

            if (!galleryData[index]) {
                return true;
            }

            if (['iframe', 'video'].indexOf(galleryData[index].type) >= 0) {
                pauseVideo();
            } else if (galleryData[index].type != 'image') {
                $player.get(0).pause();
            }

            initFancybox();

            return false;
        });

        gCarousel = new Carousel($thumbs.find('> .f-carousel').get(0), {
            center: false,
            direction: rlLangDir,
            on: {
                ready: function(){
                    $('.f-button').click(function(){
                        clearInterval(slideshowTimer);
                    });
                }
            },
            Dots: false,
            breakpoints: {
                '(max-width: 767px)': {
                    center: true
                },
            },
        });
    });

    var initFancybox = function(){
        Fancybox.show(galleryData, {
            startIndex: index,
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

    var loadImage = function(obj){
        if (media_query == 'mobile'){
            return false;
        }

        if (index == $items.index(obj)) {
            return false;
        }

        index = $items.index(obj);
        var timer = false;

        $items.removeClass('active');
        $(obj).addClass('active');

        if ($player.length) {
            $player.get(0).pause();
        }
        pauseVideo();

        // Locked media
        if ($(obj).hasClass('locked')) {
            $preview.attr('class', 'preview');
            $preview.addClass('locked');

            var type_class = $(obj).find('.play').length ? 'fake-video' : 'picture';
            $preview.addClass(type_class);

            return false;
        }

        // Load media
        if (galleryData[index].type == 'image') {
            $preview.attr('class', 'preview');

            timer = setTimeout(function(){
                $(obj).find('img').after(
                    $('<span>', {class: 'img-loading'})
                );
            }, 100);

            var img = new Image();
            img.onload = function(){
                clearTimeout(timer);
                $previewPic.attr('src', galleryData[index].src);

                $(obj).find('.img-loading').fadeOut('normal', function(){
                    $(this).remove();
                });
            }
            img.src = galleryData[index].src;
        } else {
            if (['iframe', 'video'].indexOf(galleryData[index].type) >= 0) {
                $preview.attr('class', 'preview video');
                loadVideo(galleryData[index].src);
            } else {
                if (!$player.length) {
                    $player = $('<video>')
                        .attr('id', 'player')
                        .attr('controls', 'true')
                        .append(
                            $('<source>').attr('type', 'video/mp4')
                        );
                    $preview.append($player);
                }

                $player.find('source').attr('src', galleryData[index].src);
                $player.load();
                $preview.attr('class', 'preview local');
            }
        }

        return false;
    };

    /**
     * Pause video
     *
     * @since 4.10.0
     */
    var pauseVideo = function(){
        var data = {};
        switch($ytPlayer.attr('data-type')) {
            case 'youtube':
                data = {
                    event: 'command',
                    func: 'pauseVideo'
                };
                break;

            case 'rutube':
                data = {
                    type: 'player:pause',
                    data: {}
                };
                break;
        }
        $ytPlayer.get(0).contentWindow.postMessage(JSON.stringify(data), '*');
    }

    /**
     * @deprecated 4.10.0 - Use pauseVideo() instead
     */
    var pauseYoutubeVideo = function(){}

    /**
     * @deprecated 4.10.0 - Use loadVideo() instead
     */
    var loadYoutubeVideo = function(key){}

    /**
     * Load video service video
     *
     * @since 4.10.0
     *
     * @param string href - Video embed href
     */
    var loadVideo = function(href){
        $ytPlayer
            .attr('src', href)
            .attr('data-type', galleryData[index].videoType);
    }

    var openNext = function(){
        var $next = $items.filter('.active').next();

        if ($next.length) {
            if (!$next.hasClass('is-selected') && gCarousel) {
                gCarousel.slideNext();
            }
            $next.click();
        } else {
            if (gCarousel) {
                gCarousel.slideNext();
            }
            $items.filter('[data-index=0]').click();
        }
    }

    // Thumbnail click handler
    $items.click(function(e){
        $slide = $(this);

        if (e.originalEvent) {
            clearInterval(slideshowTimer);
        }

        if (media_query == 'mobile') {
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
                initFancybox();
            }
        } else {
            return loadImage(this);
        }
    });

    // Nav buttons click handler
    $navButtons.click(function(){
        var view = $(this).attr('class').replace('nav-button ', '');
        $media.attr('data-view', view).attr('class', view);

        if (view == 'map' && !mapLoaded) {
            flUtil.loadStyle(rlConfig['mapAPI']['css']);
            flUtil.loadScript(rlConfig['mapAPI']['js'], function(){
                flMap.init($('div.listing-details div.map-container'), {
                    control: 'topleft',
                    zoom: {/literal}{$config.map_default_zoom}{literal},
                    addresses: [{
                        latLng: '{/literal}{$location.direct}',
                        content: '{$location.show}{literal}'
                    }]
                });
            });
            mapLoaded = true;
        }

        return false;
    });

    loadVideo(galleryData[index].src);

    // Slideshow
    if (galleryData.length && rlConfig.gallery_slideshow && media_query != 'mobile') {
        slideshowTimer = setInterval(function(){
            if (media_query == 'mobile') {
                clearInterval(slideshowTimer);
                return;
            }

            openNext();
        }, rlConfig.gallery_slideshow_delay * 1000);
    }
});

{/literal}
</script>

<!-- listing picture gallery tpl end -->
