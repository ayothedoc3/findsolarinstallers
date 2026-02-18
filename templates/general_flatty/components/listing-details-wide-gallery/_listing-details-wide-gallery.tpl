<!-- listing picture wide gallery tpl -->

{if $listing_type.Photo && $photos && $listing_type.Escort_Type == 'single'}
    <div class="wide-gallery">
        <div class="f-carousel">
            <div class="f-carousel__viewport">
                <div class="f-carousel__track d-flex">
                    {foreach from=$photos item='photo' name='photosF'}
                        <div class="f-carousel__slide{if $photo.Type == 'video'} video-slide{else} picture-slide{/if}{if !$allow_photos && !$smarty.foreach.photosF.first} locked{/if}">
                        {if $photo.Type == 'video'}
                            {if $allow_photos || $smarty.foreach.photosF.first}
                                {if $photo.video_key}
                                    <img src="{$rlTplBase}img/blank16x10.gif" />
                                    <iframe src="{$photo.href}" frameBorder="0" allow="autoplay" id="embed_{$smarty.foreach.photosF.index}"></iframe>
                                {else}
                                    <img src="{$rlTplBase}img/blank16x10.gif" />
                                    <video controls id="local_video_{$smarty.foreach.photosF.index}">
                                        <source src="{$photo.href}" type="video/mp4">
                                    </video>
                                {/if}
                            {else}
                                <img alt="" src="{$rlTplBase}img/blank_10x7.gif" />
                            {/if}
                        {else}
                            <img alt="{if $photo.Description}{$photo.Description|escape:'html'}{else}{$pageInfo.name|escape:'html'}{/if}"
                                 title="{if $photo.Description}{$photo.Description}{else}{$pageInfo.name}{/if}"
                                 src="{if $allow_photos || $smarty.foreach.photosF.first}{$photo.Photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}" />
                        {/if}

                        {if !$allow_photos && !$smarty.foreach.photosF.first}
                            <div class="picture_locked">
                                <div>
                                    <div class="restricted-content d-flex flex-column align-items-center">
                                    <img src="{$rlTplBase}img/blank.gif" />
                                    {if $isLogin}
                                        <p class="pl-3 pr-3">{if $photo.Type == 'video'}{$lang.watch_video_not_available}{else}{$lang.view_picture_not_available}{/if}</p>
                                        <span>
                                            <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                                        </span>
                                    {else}
                                        <p class="pl-3 pr-3">{if $photo.Type == 'video'}{$lang.watch_video_hint}{else}{$lang.view_picture_hint}{/if}</p>
                                        <span>
                                            <a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
                                        </span>
                                    {/if}
                                    </div>
                                </div>
                            </div>
                        {else}
                            {rlHook name='tplListingDetailsGalleryPreview'}
                        {/if}
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

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        flUtil.loadStyle([rlConfig['libs_url']+'fancyapps/fancybox.css', rlConfig['libs_url']+'fancyapps/carousel.css']);
        flUtil.loadScript(rlConfig['libs_url']+'fancyapps/fancybox.umd.js', function(){
            var players = [];
            var $wideGallery = $('.wide-gallery');
            var allowEnlarge = false;
            var activeSlideIndex = 0;

            var gCarousel = new Carousel($wideGallery.find('> .f-carousel').get(0), {
                center: true,
                on: {
                    'Panzoom.startAnimation': (carousel) => {
                      allowEnlarge = false;
                    },
                    'Panzoom.endAnimation': (carousel) => {
                      allowEnlarge = true;
                    },
                },
            });

            $wideGallery.find('.f-carousel__slide')
                .on('mousedown', function(){
                    allowEnlarge = true;
                })
                .on('mouseup', function(){
                    if (!allowEnlarge) {
                        return;
                    }

                    var index = $wideGallery.find('.f-carousel__slide').index(this);

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
                })
                .on('mouseenter', function(){
                    activeSlideIndex = $wideGallery.find('.f-carousel__slide').index(this);
                });

            var stopAllVideoExceptActive = function(){
                for (var i in players) {
                    if (i == activeSlideIndex) {
                        continue;
                    }

                    var data = {};
                    switch(galleryData[i].videoType) {
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
                    players[i].contentWindow.postMessage(JSON.stringify(data), '*');
                }
            }

            $wideGallery.find('.f-carousel__slide:not(.locked) iframe').each(function(){
                let index = parseInt($(this).attr('id').split('_')[1]);
                players[index] = this;
                this.contentWindow.postMessage("{event: 'listening'}", '*');
            });

            window.addEventListener('message', function(e) {
                var message = JSON.parse(e.data);
                if (message.type == 'player:changeState' && message.data.state == 'playing') {
                    stopAllVideoExceptActive();
                };
            });
        });
    });

    {/literal}
    </script>
{/if}

<!-- listing picture wide gallery tpl -->
