<!-- remove listing tpl -->

{if $show_form}
    <div class="highlight">
        {$lang.remote_delete_listing_confirm}

        <div class="pt-4">
            <a class="no-underline"
                href="{pageUrl key='listing_remove' vars="id=`$smarty.get.id`&hash=`$smarty.get.hash`&confirm"}"
            >
                <input type="button" value="{$lang.delete}" />
            </a>
            <a class="no-underline"
                href="{pageUrl key=$listing_type.My_key vars="incomplete=`$smarty.get.id`&step=`$listing.Last_step`"}"
            >
                <input type="button" value="{$lang.complete_posting}" />
            </a>
        </div>
    </div>
{elseif isset($smarty.get.complete)}
    {phrase key="listing_removed"}
{/if}

<!-- remove listing tpl end -->