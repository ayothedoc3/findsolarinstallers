<!-- 2checkout gateway -->

<input type="hidden" name="2co-token" id="2co-token" value="" />

<div style="max-width: 450px;">
    <div class="mt-4 mb-3" style="padding-right: 25px;">
        <div class="mb-2">{$lang.card_holder_name}</div>
        <div class="field">
            <input type="text" name="2co[card_name]" id="2co-name" class="w-100" maxlength="64" value="{$smarty.post.2co.card_name}" />
        </div>
    </div>
    <div id="card-element"></div>
</div>

<script>

var tc_btnEl = '#btn-checkout, #form-checkout input[type="submit"]';
var tc_btnVal = $(tc_btnEl).val();

{literal}
$(document).ready(function() {
    $(tc_btnEl).click(function() {
        if ($('input[name="gateway"]:checked').val() != '2co') {
            $('#2co-form').remove();
            return true;
        }
        $(tc_btnEl).val(lang['loading']);
        $(tc_btnEl).attr('disabled', true);
        return true;
    });

    $("input.numeric").numeric();
    $('input#use-account-info').prop('checked', true);
    $('#billing-form').hide();

    flUtil.loadScript('https://2pay-js.2checkout.com/v1/2pay.js', function(){
        // Initialize the 2Pay.js client.
        let jsPaymentClient = new TwoPayClient('{/literal}{$config.2co_id}{literal}');

        // Create the component that will hold the card fields.
        let component = jsPaymentClient.components.create('card', {
            margin: 0,
            fontFamily: 'Helvetica, sans-serif',
            fontSize: '1rem',
            fontWeight: '400',
            lineHeight: '1.5',
            color: '#212529',
            textAlign: 'left',
            // backgroundColor: '#ffffff',
            backgroundColor: $('body').css('background-color'),
            '*': {
                'boxSizing': 'border-box'
            },
            '.no-gutters': {
                marginRight: 0,
                marginLeft: 0
            },
            '.row': {
                display: 'flex',
                flexWrap: 'wrap'
            },
            '.col': {
                flexBasis: '0',
                flexGrow: '1',
                maxWidth: '100%',
                padding: '0',
                position: 'relative',
                width: '100%'
            },
            'div': {
                display: 'block'
            },
            '.field-container': {
                paddingBottom: '14px'
            },
            '.field-wrapper': {
                paddingRight: '25px'
            },
            '.input-wrapper': {
                position: 'relative'
            },
            label: {
                display: 'inline-block',
                marginBottom: '9px',
                // color: '#313131',
                color: $('body').css('color'),
                fontSize: '14px',
                fontWeight: '300',
                lineHeight: '17px'
            },
            'input': {
                overflow: 'visible',
                margin: 0,
                fontFamily: 'inherit',
                display: 'block',
                width: '100%',
                height: '42px',
                padding: '10px 12px',
                fontSize: '18px',
                fontWeight: '400',
                lineHeight: '22px',
                // color: '#313131',
                color: $('input[name="2co[card_name]"]').css('color'),
                // backgroundColor: '#fff',
                backgroundColor: $('input[name="2co[card_name]"]').css('background-color'),
                backgroundClip: 'padding-box',
                border: '1px solid #CBCBCB',
                borderRadius: '3px',
                transition: 'border-color .15s ease-in-out,box-shadow .15s ease-in-out',
                outline: 0
            },
            'input:focus': {
                border: '1px solid #5D5D5D',
                // backgroundColor: '#FFFDF2'
                backgroundColor: $('input[name="2co[card_name]"]').css('background-color'),
            },
            '.is-error input': {
                border: '1px solid #D9534F'
            },
            '.is-error input:focus': {
                backgroundColor: '#D9534F0B'
            },
            '.is-valid input': {
                border: '1px solid #1BB43F'
            },
            '.is-valid input:focus': {
                backgroundColor: '#1BB43F0B'
            },
            '.validation-message': {
                color: '#D9534F',
                fontSize: '10px',
                fontStyle: 'italic',
                marginTop: '6px',
                marginBottom: '-5px',
                display: 'block',
                lineHeight: '1'
            },
            '.card-expiration-date': {
                paddingRight: '.5rem'
            },
            '.is-empty input': {
                color: '#EBEBEB'
            },
            '.lock-icon': {
                top: 'calc(50% - 7px)',
                right: '10px'
            },
            '.valid-icon': {
                top: 'calc(50% - 8px)',
                right: '-25px'
            },
            '.error-icon': {
                top: 'calc(50% - 8px)',
                right: '-25px'
            },
            '.card-icon': {
                top: 'calc(50% - 10px)',
                left: '10px',
                display: 'none'
            },
            '.is-empty .card-icon': {
                display: 'block'
            },
            '.is-focused .card-icon': {
                display: 'none'
            },
            '.card-type-icon': {
                right: '30px',
                display: 'block'
            },
            '.card-type-icon.visa': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.mastercard': {
                top: 'calc(50% - 14.5px)'
            },
            '.card-type-icon.amex': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.discover': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.jcb': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.dankort': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.cartebleue': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.diners': {
                top: 'calc(50% - 14px)'
            },
            '.card-type-icon.elo': {
                top: 'calc(50% - 14px)'
            }
        });

        // Mount the card fields component in the desired HTML tag. This is where the iframe will be located.
        component.mount('#card-element');

        // Handle form submission.
        $(tc_btnEl).click(function(event) {
            event.preventDefault();

            // Extract the Name field value
            const billingDetails = {
                name: document.getElementById('2co-name').value
            };

            // Call the generate method using the component as the first parameter
            // and the billing details as the second one
            jsPaymentClient.tokens.generate(component, billingDetails).then((response) => {
                $('#2co-token').val(response.token);
                $('#form-checkout').submit();
            }).catch((error) => {
                printMessage('error', error.message);
                $(tc_btnEl).removeAttr('disabled').val(tc_btnVal);
            });
        });
    });
});
{/literal}
</script>

<!-- end 2checkout gateway -->
