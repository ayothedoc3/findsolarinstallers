<!-- search on map section tpl -->

{mapsAPI}

{addJS file=$rlTplBase|cat:'controllers/search_map/search_map.js'}
{addCSS file=$rlTplBase|cat:'components/search-on-map-section/search-on-map-section.css'}

<div class="search-map-section relative">
    <div id="map_container" class="search-map-section__cont w-100"></div>
    <span class="loading map-loading"><span class="loading-spinner"></span></span>

    <div class="search-map-area overflow-hidden">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'horizontal_search.tpl' search_forms=$map_search_forms}
    </div>

    <svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        {include file='../img/svg/userLocation.svg'}
    </svg>
    
    <script class="fl-js-dynamic">
    var default_map_location = '{$default_map_location|escape:'quotes'}';
    var default_map_coordinates = [{if $smarty.post.loc_lat && $smarty.post.loc_lng}{$smarty.post.loc_lat},{$smarty.post.loc_lng}{else}{$config.search_map_location}{/if}];
    var default_map_zoom = {if $config.search_map_location_zoom}{$config.search_map_location_zoom}{else}14{/if};
    var listings_limit_desktop = {if $config.map_search_listings_limit}{$config.map_search_listings_limit}{else}500{/if};
    var listings_limit_mobile = {if $config.map_search_listings_limit_mobile}{$config.map_search_listings_limit_mobile}{else}75{/if};

    lang['count_properties'] = '{$lang.count_properties}';
    lang['number_property_found'] = '{$lang.number_property_found}';
    lang['no_properties_found'] = '{$lang.no_properties_found}';
    lang['map_listings_request_empty'] = '{$lang.map_listings_request_empty}';
    lang['short_price_k'] = '{$lang.short_price_k}';
    lang['short_price_m'] = '{$lang.short_price_m}';
    lang['short_price_b'] = '{$lang.short_price_b}';
    lang['enter_a_location'] = '{$lang.enter_a_location}';

    {literal}

    var mapTabBar = mapSearch.tabBar;
    mapSearch.tabBar = function(){
        $('.leaflet-control-container').addClass('point1');

        if (typeof mapTabBar == 'function') {
            mapTabBar.call(mapSearch);
        }
    }

    mapSearch.init({
        mapContainer: $('#map_container'),
        mapCenter: default_map_coordinates,
        mapZoom: default_map_zoom,
        mapAltLocation: default_map_location,
        searchForm: $('.search-map-area .search-block-content'),
        tabBar: $('.search-map-area .form-switcher'),
        desktopLimit: listings_limit_desktop,
        mobileLimit: listings_limit_mobile,
        geocoder: true
    });
    {/literal}
    </script>
</div>

<!-- search on map section tpl end -->
