<!-- home tpl - solar ui improvements -->{strip}

{rlHook name='homeTop'}

<section class="solar-hero">
    <div class="solar-hero-overlay"></div>
    <div class="point1 solar-hero-content">
        <p class="solar-eyebrow">Trusted by homeowners nationwide</p>
        <h1>Find the Right Solar Installer for Your Home</h1>
        <p class="solar-hero-subtitle">
            Compare ratings, certifications, and services from verified solar companies
            across the United States.
        </p>

        <div class="solar-hero-badges">
            <span>No spam leads</span>
            <span>Free to compare</span>
            <span>US-wide coverage</span>
        </div>

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
                        <option value="4">EV Charger + Solar</option>
                        <option value="5">Maintenance &amp; Repair</option>
                        <option value="6">Solar Pool Heating</option>
                    </select>
                </div>
                <button type="submit" class="solar-search-btn">Search</button>
            </div>
        </form>
    </div>
</section>

<section class="solar-stats">
    <div class="point1">
        <div class="solar-stats-grid">
            <div class="solar-stat">
                <span class="solar-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
                </span>
                <div class="solar-stat-copy">
                    <span class="solar-stat-number">5,000+</span>
                    <span class="solar-stat-label">Solar Installers</span>
                </div>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </span>
                <div class="solar-stat-copy">
                    <span class="solar-stat-number">50</span>
                    <span class="solar-stat-label">US States</span>
                </div>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9"></polygon></svg>
                </span>
                <div class="solar-stat-copy">
                    <span class="solar-stat-number">25,000+</span>
                    <span class="solar-stat-label">Customer Reviews</span>
                </div>
            </div>
            <div class="solar-stat">
                <span class="solar-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 13c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3 8 3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                </span>
                <div class="solar-stat-copy">
                    <span class="solar-stat-number">100%</span>
                    <span class="solar-stat-label">Free to Compare</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="solar-how-it-works">
    <div class="point1">
        <div class="solar-section-head">
            <p class="solar-section-eyebrow">Simple process</p>
            <h2>How It Works</h2>
            <p>Finding the right solar installer takes just three simple steps.</p>
        </div>
        <div class="solar-steps">
            <div class="solar-step">
                <div class="solar-step-top">
                    <span class="solar-step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.2-4.2"></path></svg>
                    </span>
                    <span class="solar-step-number">01</span>
                </div>
                <h3>Search</h3>
                <p>Enter your location and find companies in your area with detailed profiles.</p>
            </div>
            <div class="solar-step">
                <div class="solar-step-top">
                    <span class="solar-step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                    </span>
                    <span class="solar-step-number">02</span>
                </div>
                <h3>Compare</h3>
                <p>Review ratings, certifications, panel brands, and services side by side.</p>
            </div>
            <div class="solar-step">
                <div class="solar-step-top">
                    <span class="solar-step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </span>
                    <span class="solar-step-number">03</span>
                </div>
                <h3>Get Quotes</h3>
                <p>Contact top-rated installers directly and request free consultation quotes.</p>
            </div>
        </div>
    </div>
</section>

<section class="solar-services">
    <div class="point1">
        <div class="solar-section-head solar-section-center">
            <p class="solar-section-eyebrow">Categories</p>
            <h2>Browse by Service</h2>
            <p>Explore installers by the type of solar service you need.</p>
        </div>
        <div class="solar-services-grid">
            <a href="{categoryUrl id=2001}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1Z"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>Residential Solar</strong><em>2,400+ installers</em></span>
                <p>Home solar panel systems and rooftop installations.</p>
            </a>
            <a href="{categoryUrl id=2002}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 7h6M9 12h6M9 17h6"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>Commercial Solar</strong><em>890+ installers</em></span>
                <p>Business and industrial solar energy solutions.</p>
            </a>
            <a href="{categoryUrl id=2004}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="4" width="10" height="16" rx="2"></rect><path d="M10 2h4"></path><path d="M12 9v6"></path><path d="M9 12h6"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>Battery Storage</strong><em>1,200+ installers</em></span>
                <p>Tesla Powerwall, Enphase, and backup battery systems.</p>
            </a>
            <a href="{categoryUrl id=2006}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 3 14h8l-1 8 11-13h-8Z"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>EV Charger + Solar</strong><em>640+ installers</em></span>
                <p>Electric vehicle charging with solar integration.</p>
            </a>
            <a href="{categoryUrl id=2003}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.3-3.3a5.5 5.5 0 0 1-7.6 7.6l-6.9 6.9a2 2 0 1 1-2.8-2.8l6.9-6.9a5.5 5.5 0 0 1 7.6-7.6z"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>Maintenance &amp; Repair</strong><em>1,100+ installers</em></span>
                <p>Solar panel cleaning, repair, and system monitoring.</p>
            </a>
            <a href="{categoryUrl id=2005}" class="solar-service-card">
                <span class="solar-service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 15c6.7-6 13.3 0 20-6"></path><path d="M2 19c6.7-6 13.3 0 20-6"></path></svg>
                </span>
                <span class="solar-service-title-row"><strong>Solar Pool Heating</strong><em>380+ installers</em></span>
                <p>Solar-powered pool and water heating systems.</p>
            </a>
        </div>
    </div>
</section>

<section class="solar-locations">
    <div class="point1">
        <div class="solar-section-head solar-locations-head">
            <div>
                <p class="solar-section-eyebrow">Popular areas</p>
                <h2>Browse by Location</h2>
                <p>Find solar installers in the most popular states across the US.</p>
            </div>
            <a href="{pageUrl key='search_on_map'}" class="solar-link-arrow">View coverage map</a>
        </div>
        <div class="solar-locations-grid">
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>California</strong><span>820+</span></div>
                <div class="solar-location-tags"><span>Los Angeles</span><span>San Diego</span><span>San Francisco</span><span>Sacramento</span></div>
            </article>
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>Texas</strong><span>540+</span></div>
                <div class="solar-location-tags"><span>Houston</span><span>Austin</span><span>Dallas</span><span>San Antonio</span></div>
            </article>
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>Florida</strong><span>460+</span></div>
                <div class="solar-location-tags"><span>Miami</span><span>Tampa</span><span>Orlando</span><span>Jacksonville</span></div>
            </article>
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>Arizona</strong><span>380+</span></div>
                <div class="solar-location-tags"><span>Phoenix</span><span>Tucson</span><span>Scottsdale</span><span>Mesa</span></div>
            </article>
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>New York</strong><span>310+</span></div>
                <div class="solar-location-tags"><span>New York City</span><span>Buffalo</span><span>Albany</span><span>Rochester</span></div>
            </article>
            <article class="solar-location-card">
                <div class="solar-location-title"><strong>Colorado</strong><span>270+</span></div>
                <div class="solar-location-tags"><span>Denver</span><span>Colorado Springs</span><span>Boulder</span><span>Fort Collins</span></div>
            </article>
        </div>
    </div>
</section>

{if $featured_listings}
<section class="solar-featured">
    <div class="point1">
        <div class="solar-section-head solar-section-center">
            <p class="solar-section-eyebrow">Featured</p>
            <h2>Featured Solar Companies</h2>
            <p>Discover vetted installers with standout ratings and services.</p>
        </div>

        <div class="solar-featured-grid">
            {foreach from=$featured_listings item='listing' name='featuredF'}
                {if $smarty.foreach.featuredF.index < 6}
                    <article class="solar-featured-card">
                        <a href="{$listing.url}" title="{$listing.listing_title}">
                            <div class="solar-featured-img">
                                {if $listing.Main_photo}
                                    <img src="{$smarty.const.RL_FILES_URL}{$listing.Main_photo}" alt="{$listing.listing_title}" loading="lazy" />
                                {else}
                                    <div class="solar-no-photo">
                                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="4.5"></circle><path d="M12 2v3M12 19v3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M2 12h3M19 12h3M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12"></path></svg>
                                    </div>
                                {/if}
                                {if $listing.Featured}
                                    <span class="solar-badge-featured">Featured</span>
                                {/if}
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
                                    <div class="solar-services-tags">{$listing.fields.services_offered.value}</div>
                                {/if}
                            </div>
                        </a>
                    </article>
                {/if}
            {/foreach}
        </div>
        <div class="solar-view-all">
            <a href="{pageUrl key='lt_listings'}" class="solar-btn-outline">View all solar companies</a>
        </div>
    </div>
</section>
{/if}

<section class="solar-cta">
    <div class="point1">
        <div class="solar-cta-inner">
            <h2>Are You a Solar Installation Company?</h2>
            <p>
                Get listed on SolarListings and reach thousands of homeowners
                searching for trusted installers in their area.
            </p>
            <div class="solar-cta-buttons">
                <a href="{pageUrl key='registration'}" class="solar-btn-primary">List your company free</a>
                <a href="{pageUrl key='contact_us'}" class="solar-btn-outline-light">Learn more</a>
            </div>
        </div>
    </div>
</section>

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
