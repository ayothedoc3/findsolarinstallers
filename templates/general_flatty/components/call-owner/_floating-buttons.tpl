<!-- Call owner mobile floating buttons -->

{if !$is_owner && $pageInfo.Controller == 'listing_details' && $config.show_call_owner_button && $allow_contacts}
    <div class="contact-owner-navbar d-lg-none hlight w-100">
        <div class="point1 container mx-auto">
            <div class="d-flex flex-wrap gap-3 pt-3 pb-3 content-padding">
                {if $config.messages_module}<input type="button" class="flex-fill contact-owner" value="{phrase key='contact_owner'}" accesskey="{phrase key='contact_owner'}" />{/if}
                <input class="flex-fill call-owner" data-listing-id="{$listing_data.ID}" type="button" value="{phrase key='call_owner'}" />
            </div>
        </div>
    </div>

    <script>
    {literal}

    (function(){
        $('body').append(
            $('<div>')
                .addClass('contact-owner-navbar-crossbar')
                .css('flex', '0 0 ' + $('.contact-owner-navbar').height() + 'px')
                .height($('.contact-owner-navbar').height())
        );
    })();

    {/literal}
    </script>
{/if}

<!-- Call owner mobile floating buttons end -->
