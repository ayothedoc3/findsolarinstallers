<!-- field_out availability -->

{foreach from=$item.value item='availability'}
    <div class="table-cell clearfix" id="df_field_availability_{$availability.day}">
        <div class="name">{$availability.title}:</div>
        <div class="value">{$availability.from} - {$availability.to}</div>
    </div>
{/foreach}

<!-- field_out availability end -->
