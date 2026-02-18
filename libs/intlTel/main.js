
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: MAIN.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

const errorMap = ['invalid_number', null, 'too_short', null, 'invalid_number'];
const langCodes = [
    'ar','bg','bn','bs','ca','cs','da','de','ee','el','en','es','fa',
    'fi','fr','hi','hr','hu','id','it','ja','ko','mr','nl','no','pl',
    'pt','ro','ru','sk','sv','te','th','tr','uk','ur','uz','vi','zh'
];

const intlTelInit = function(i18n){
    $('.phone-field').each(function(){
        const options = {
            i18n: i18n ? i18n : null,
            strictMode: true,
            separateDialCode: $(this).data('show-dial') ? true : false,
            showFlags: $(this).data('show-flag') ? true : false,
            loadUtils: () => import(rlConfig.libs_url + 'intlTel/utils.js'),
        };
        const country = $(this).data('country');

        if (country) {
            options.onlyCountries = [country];
            options.allowDropdown = false;
        } else {
            options.initialCountry = rlConfig['user_country_code'];
        }

        const iti = intlTelInput(this, options);

        $(this).on('blur keyup', function(e){
            const val = $(this).val().trim();
            const $cont = $(this).closest('.field');
            const $error = $cont.find('.phone-field-error');
            const $inputValid = $cont.find('.phone-field-valid');
            const $inputNumber = $cont.find('.phone-field-number');

            if (val && !iti.isValidNumber()) {
                if (e.type == 'blur') {
                    const errorCode = iti.getValidationError();
                    const phraseKey = errorMap[errorCode] || errorMap[0];

                    $(this).addClass('error');
                    $error.text(lang[phraseKey]).removeClass('hide');
                }

                $inputValid.val(0);
                $inputNumber.val($(this).val());
            } else {
                if (e.type == 'blur') {
                    $(this).removeClass('error');
                    $error.text('').addClass('hide');
                }

                $inputValid.val(1);
                $inputNumber.val(iti.getNumber(intlTelInput.utils.numberFormat.INTERNATIONAL));
            }
        })

        // Validate on initialization
        iti.promise.then(() => {
            if (iti.getNumber() && iti.isValidNumber()) {
                $(this).closest('.field').find('.phone-field-valid').val('1');
            }
        });
    });
}

if (rlLang && langCodes.indexOf(rlLang) >= 0) {
    const moduleUrl = rlConfig.libs_url + 'intlTel/i18n/' + rlLang + '/index.js';

    import(moduleUrl).then(countryTranslations => {
        intlTelInit(countryTranslations.default);
    });
} else {
    intlTelInit();
}
