{assign var='count_replace' value=`$smarty.ldelim`count`$smarty.rdelim`}

<script id="tmplListing" type="text/x-jquery-tmpl">
{literal}

${($data.count_in_location = '{/literal}{$lang.count_properties_in_location|replace:$count_replace:'[count]'}{literal}'),''}
${($data.group_location_hint = '{/literal}{$lang.group_location_hint}{literal}'),''}
${($data.seo_base = '{/literal}{$smarty.const.SEO_BASE}{literal}'),''}

<article class="item col-sm-6{{if fd == 1 && gc == 1}} featured{{/if}}{{if gc > 1}} group{{/if}}" id="map_ad_${ID}">
    <div class="main-column clearfix">
        <a {{if gc == 1}}target="_blank"{{/if}} href="{{if gc > 1}}javascript://{{else}}${url}{{/if}}">
            <div class="picture{{if !img}} no-picture{{/if}}">
                <img src="{{if img}}${img}{{else}}{/literal}{$rlTplBase}{literal}img/blank_10x7.gif{{/if}}"
                {{if img_x2}}srcset="${img_x2} 2x"{{/if}} />
                {{if tmplMapListingHookData}}{{html tmplMapListingHookData}}{{/if}}
                {{if fd == 1 && gc == 1}}<div class="label"><div title="{/literal}{$lang.featured}{literal}">{/literal}{$lang.featured}{literal}</div></div>{{/if}}

                {{if gc > 1}}<mark class="group"><span>{{html String(count_in_location).replace(/(\[count\])/gi, gc)}}</span></mark>{{/if}}
            </div>
        </a>

        <div class="data-container">
            <div class="two-inline">
                {{if gc == 1}}
                <ul class="nav-column">
                    <li id="fav_${ID}" class="favorite add"><span class="icon"></span></li>
                </ul>
                {{/if}}
                {{if price}}
                <div class="system">
                    <div class="price-tag">
                        {{if price_from}}{/literal}{$lang.price_from}{literal}{{/if}}
                        <span>
                            {{if price_contact_form}}
                                <a href="javascript://"
                                    class="contact-owner price-contact-form"
                                    data-listing-id="${ID}"
                                    data-account-id="${Account_ID}"
                                >
                            {{/if}}
                            ${price}
                            {{if price_contact_form}}
                                </a>
                            {{/if}}
                        </span>
                        {{if srk == 2 && tf}}/ ${tf}{{/if}}
                    </div>
                </div>
                {{/if}}
            </div>

            <ul class="info">
                {{if gc > 1}}
                    <li class="group-info">
                        ${group_location_hint}
                    </li>
                    {{else}}
                    <li class="title">
                        <a target="_blank" href="${url}" title="${title}" class="link-large text-overflow">${title}</a>
                    </li>
                    {{if bds > 0 || bts > 0 || sf}}
                    <li class="services">
                        {{if bds > 0}}<span title="" class="badrooms">${bds}</span>{{/if}}{{if bts > 0}}<span title="" class="bathrooms">${bts}</span>{{/if}}{{if sf}}<span title="" class="square_feet">${sf}</span>{{/if}}
                    </li>
                    {{/if}}
                    <li class="fields">
                        {{each fields_data}}
                            <span>${$value}</span>
                        {{/each}}
                    </li>
                {{/if}}
            </ul>
        </div>
    </div>
</article>

{/literal}
</script>
