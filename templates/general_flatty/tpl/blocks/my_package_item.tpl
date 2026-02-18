<!-- my package item -->

<li class="d-flex flex-column flex-md-row mb-4">
    <div class="package__frame" {if $package.Color}style="background-color: #{$package.Color};border-color: #{$package.Color};"{/if}>
        <h3 class="package__name mb-3">{$package.name}</h3>

        <div class="d-flex flex-column flex-md-row">
            <div class="mr-md-4">
                <div class="package__price mb-3">
                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                    {$package.Price}
                    {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                </div>
                <div class="table-cell small clearfix">
                    <div class="name">{$lang.plan_live_for}</div>
                    <div class="value">
                        {if $package.Plan_period}
                            {$package.Plan_period} {$lang.days}
                        {else}
                            {$lang.unlimited_short}
                        {/if}
                    </div>
                </div>
            </div>

            <div class="package__listing-info">
                <div class="table-cell small clearfix">
                    <div class="name">{$lang.listing_live_for}</div>
                    <div class="value">
                        {if $package.Listing_period}
                            {$package.Listing_period} {$lang.days}
                        {else}
                            {$lang.unlimited_short}
                        {/if}
                    </div>
                </div>
                <div class="table-cell small clearfix">
                    <div class="name">{$lang.photos_count}</div>
                    <div class="value">
                        {if $package.Image_unlim}
                            {$lang.unlimited_short}
                        {else}
                            {$package.Image}
                        {/if}
                    </div>
                </div>
                <div class="table-cell small clearfix">
                    <div class="name">{$lang.video}</div>
                    <div class="value">
                        {if $package.Image_unlim}
                            {$lang.unlimited_short}
                        {else}
                            {if $package.Video_unlim}
                                {$lang.unlimited_short}
                            {else}
                                {$package.Video}
                            {/if}
                        {/if}
                    </div>
                </div>

                {rlHook name='tplMyPackageItemListingInfo'}
            </div>
        </div>
    </div>

    <div class="package__stats align-content-center ml-md-3 ml-xl-5">
        {if $package.Advanced_mode}
            <div class="table-cell clearfix">
                <div class="name">{$lang.standard}</div>
                <div class="value{if empty($package.Standard_remains) && !empty($package.Standard_listings)} overdue{/if}">
                    {if empty($package.Standard_listings)}
                        <b>{$lang.unlimited_short}</b>
                    {else}
                        {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                        {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                        {$lang.rest_of_amount|replace:$rRest:$package.Standard_remains|replace:$rAmount:$package.Standard_listings}
                    {/if}
                </div>
            </div>
            <div class="table-cell clearfix">
                <div class="name">{$lang.featured}</div>
                <div class="value{if empty($package.Featured_remains) && !empty($package.Featured_listings)} overdue{/if}">
                    {if empty($package.Featured_listings)}
                        <b>{$lang.unlimited_short}</b>
                    {else}
                        {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                        {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                        {$lang.rest_of_amount|replace:$rRest:$package.Featured_remains|replace:$rAmount:$package.Featured_listings}
                    {/if}
                </div>
            </div>
        {else}
            <div class="table-cell">
                <div class="name">
                {if $package.Featured}
                    {$lang.featured}
                {else}
                    {$lang.standard}
                {/if}
                </div>
                <div class="value{if empty($package.Listings_remains) && !empty($package.Listing_number)} overdue{/if}">
                    {if empty($package.Listing_number)}
                        <b>{$lang.unlimited_short}</b>
                    {else}
                        {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                        {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                        {$lang.rest_of_amount|replace:$rRest:$package.Listings_remains|replace:$rAmount:$package.Listing_number}
                    {/if}
                </div>
            </div>
        {/if}

        <div class="{$package.Exp_status} mt-2">
            {$lang.expiration_date}: {if $package.Exp_date == 'unlimited'}{$lang.unlimited_short}{else}{$package.Exp_date|date_format:$smarty.const.RL_DATE_FORMAT}{/if}
        </div>

        {if !$renew}
            <div class="mt-3">
                {if $package.Subscription}
                    <a class="unsubscription button"
                        id="unsubscription-{$package.ID}"
                        href="javascript:void(0);"
                        accesskey="{$package.ID}-{$package.Subscription_ID}-{$package.Subscription_service}">
                        {$lang.unsubscription}
                    </a>
                {elseif $package.Exp_status === 'expired' || $package.Price > 0}
                    <a class="button w-100 w-md-auto" href="{pageUrl key=$pageInfo.Key vars='renew='|cat:$package.ID}">
                        {$lang.renew}
                    </a>
                {/if}
            </div>
        {/if}
    </div>
</li>

<!-- my package item emd -->
