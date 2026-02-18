<!-- home tpl - SolarListings -->{strip}

{rlHook name='homeTop'}

{addCSS file=$rlBase|cat:'custom/templates/general_flatty/css/solar.css'}

<!-- Hero Section -->
<section class="solar-hero">
    <div class="solar-hero-overlay"></div>
    <div class="solar-hero-content">
        <h1>Find Trusted Solar Installers Near You</h1>
        <p class="solar-hero-subtitle">Compare ratings, certifications, and services from thousands of solar companies across the US</p>

        <!-- Search Form -->
        <form class="solar-search-form" method="post" action="{pageUrl key='search'}">
            <input type="hidden" name="form" value="listings_quick" />
            <div class="solar-search-fields">
                <div class="solar-search-field">
                    <label for="solar-location">{$lang.location|default:'Location'}</label>
                    <input type="text" id="solar-location" name="f[keyword_search]" placeholder="City, State or ZIP code" />
                </div>
                <div class="solar-search-field">
                    <label for="solar-service">{$lang.services_offered|default:'Service Type'}</label>
                    <select id="solar-service" name="f[services_offered][]">
                        <option value="">All Services</option>
                        <option value="1">Residential Solar</option>
                        <option value="2">Commercial Solar</option>
                        <option value="3">Battery Storage</option>
                        <option value="4">EV Charger Installation</option>
                        <option value="5">Maintenance & Repair</option>
                        <option value="6">Pool/Water Heating</option>
                    </select>
                </div>
                <button type="submit" class="solar-search-btn">Search Installers</button>
            </div>
        </form>
    </div>
</section>
<!-- Hero Section End -->

<!-- Stats Bar -->
<section class="solar-stats">
    <div class="point1">
        <div class="solar-stats-grid">
            <div class="solar-stat">
                <span class="solar-stat-number" id="stat-installers">5,000+</span>
                <span class="solar-stat-label">Solar Installers</span>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-number">50</span>
                <span class="solar-stat-label">US States</span>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-number">25,000+</span>
                <span class="solar-stat-label">Customer Reviews</span>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-number">100%</span>
                <span class="solar-stat-label">Free to Compare</span>
            </div>
        </div>
    </div>
</section>
<!-- Stats Bar End -->

<!-- How It Works -->
<section class="solar-how-it-works">
    <div class="point1">
        <h2>How It Works</h2>
        <div class="solar-steps">
            <div class="solar-step">
                <div class="solar-step-icon">1</div>
                <h3>Search</h3>
                <p>Enter your location and find solar installers in your area with detailed company profiles</p>
            </div>
            <div class="solar-step">
                <div class="solar-step-icon">2</div>
                <h3>Compare</h3>
                <p>Review ratings, certifications, panel brands, and services side by side</p>
            </div>
            <div class="solar-step">
                <div class="solar-step-icon">3</div>
                <h3>Get Quotes</h3>
                <p>Contact top-rated installers directly and get free consultation quotes</p>
            </div>
        </div>
    </div>
</section>
<!-- How It Works End -->

<!-- Featured Installers -->
{if $featured_listings}
<section class="solar-featured">
    <div class="point1">
        <h2>Featured Solar Companies</h2>
        <div class="solar-featured-grid">
            {foreach from=$featured_listings item='listing' name='featuredF'}
                {if $smarty.foreach.featuredF.index < 6}
                <div class="solar-featured-card">
                    <a href="{$listing.url}" title="{$listing.listing_title}">
                        <div class="solar-featured-img">
                            {if $listing.Main_photo}
                                <img src="{$smarty.const.RL_FILES_URL}{$listing.Main_photo}" alt="{$listing.listing_title}" loading="lazy" />
                            {else}
                                <div class="solar-no-photo">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                                </div>
                            {/if}
                            {if $listing.Featured}<span class="solar-badge-featured">Featured</span>{/if}
                        </div>
                        <div class="solar-featured-info">
                            <h4>{$listing.listing_title}</h4>
                            {if $listing.fields.google_rating.value}
                                <div class="solar-rating">
                                    <span class="solar-stars" style="--rating: {$listing.fields.google_rating.value}"></span>
                                    <span class="solar-rating-num">{$listing.fields.google_rating.value}</span>
                                    {if $listing.fields.total_reviews.value}
                                        <span class="solar-review-count">({$listing.fields.total_reviews.value} reviews)</span>
                                    {/if}
                                </div>
                            {/if}
                            {if $listing.fields.services_offered.value}
                                <div class="solar-services-tags">
                                    {$listing.fields.services_offered.value}
                                </div>
                            {/if}
                        </div>
                    </a>
                </div>
                {/if}
            {/foreach}
        </div>
        <div class="solar-view-all">
            <a href="{pageUrl key='lt_listings'}" class="solar-btn-outline">View All Solar Companies</a>
        </div>
    </div>
</section>
{/if}
<!-- Featured Installers End -->

<!-- Categories Section -->
<section class="solar-categories">
    <div class="point1">
        <h2>Browse by Service</h2>
        <div class="solar-categories-grid">
            <a href="{pageUrl key='lt_listings'}/residential-solar/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <h4>Residential Solar</h4>
                <p>Home solar panel systems and rooftop installations</p>
            </a>
            <a href="{pageUrl key='lt_listings'}/commercial-solar/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="9" y1="6" x2="15" y2="6"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                </div>
                <h4>Commercial Solar</h4>
                <p>Business and industrial solar energy solutions</p>
            </a>
            <a href="{pageUrl key='lt_listings'}/solar-battery-storage/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="6" y="4" width="12" height="18" rx="2"/><line x1="10" y1="1" x2="14" y2="1"/><line x1="12" y1="10" x2="12" y2="16"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
                </div>
                <h4>Battery Storage</h4>
                <p>Tesla Powerwall, Enphase, and backup battery systems</p>
            </a>
            <a href="{pageUrl key='lt_listings'}/ev-charger-solar/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <h4>EV Charger + Solar</h4>
                <p>Electric vehicle charging with solar integration</p>
            </a>
            <a href="{pageUrl key='lt_listings'}/solar-maintenance/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h4>Maintenance & Repair</h4>
                <p>Solar panel cleaning, repair, and system monitoring</p>
            </a>
            <a href="{pageUrl key='lt_listings'}/solar-pool-heating/" class="solar-category-card">
                <div class="solar-category-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 15c6.667-6 13.333 0 20-6"/><path d="M2 19c6.667-6 13.333 0 20-6"/></svg>
                </div>
                <h4>Solar Pool Heating</h4>
                <p>Solar-powered pool and water heating systems</p>
            </a>
        </div>
    </div>
</section>
<!-- Categories Section End -->

<!-- CTA Section -->
<section class="solar-cta">
    <div class="point1">
        <div class="solar-cta-inner">
            <h2>Are You a Solar Installation Company?</h2>
            <p>Get listed on SolarListings and reach thousands of homeowners looking for solar installers in their area.</p>
            <div class="solar-cta-buttons">
                <a href="{pageUrl key='registration'}" class="solar-btn-primary">List Your Company Free</a>
                <a href="{pageUrl key='contact_us'}" class="solar-btn-outline-light">Learn More</a>
            </div>
        </div>
    </div>
</section>
<!-- CTA Section End -->

{rlHook name='homeBottomTpl'}

<!-- removing account popup -->
{assign var='remove_account_variable' value='remove-account'}
{if isset($smarty.request.$remove_account_variable) && $smarty.request.id && $smarty.request.hash}
    {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
    {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}
    {addJS file=$rlTplBase|cat:'components/account-removing/_account-removing.js'}

    <script class="fl-js-dynamic">
    $(function(){literal}{{/literal}
        flAccountRemoving.init('{$smarty.request.id}', '{$smarty.request.hash}');
    {literal}}{/literal});
    </script>
{/if}
<!-- removing account popup end -->

{/strip}
<!-- home tpl end -->
