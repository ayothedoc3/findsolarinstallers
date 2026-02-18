<!-- Statistics section -->

<section class="listing-details-stat d-flex flex-column-reverse{if !$columnView} flex-sm-row{/if} gap-2">
    <ul class="counters">
        {if $config.count_listing_visits}<li><span class="count">{$listing_data.Shows}</span> {$lang.shows}</li>{/if}
        {rlHook name='listingDetailsCounters'}
    </ul>
    <ul class="controls{if !$columnView} offset-sm-2{/if}">
        {if $config.display_posted_date}
        <li><span class="name">{$lang.posted}:</span> {toPrettyDateTime date=$listing_data.Date}</li>
        {/if}
        {rlHook name='listingDetailsAfterStats'}
    </ul>
</section>

<!-- Statistics section end -->
