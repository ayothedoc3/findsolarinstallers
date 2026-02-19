{include file='../img/social.svg'}

<div class="bottom-line d-flex w-100 justify-content-between align-items-center flex-wrap flex-md-nowrap">
    <div class="payment-methods">
        <img class="payment-methods-img" src="{$rlTplBase}img/blank.gif" />
    </div>

    <div class="w-100 footer-cp text-center">
        &copy; {$smarty.now|date_format:'%Y'} <a title="SolarListings" href="{$rlBase}">SolarListings</a> &mdash; Find Solar Installers Near You
    </div>

    <div class="icons justify-content-end d-flex">
        {include file='menus/footer_social_icons.tpl' marginClass='ml-3'}
    </div>
</div>
