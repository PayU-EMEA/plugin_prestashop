/**
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

$(document).ready(function(){
    var apply_business_partner_type = function(type) {
        if (type in business_platforms) {
            $('fieldset.hide').hide();
            $('.'+business_platforms[type].type).show();
        }
    };
    var platform_select = $('#PAYU_PAYMENT_PLATFORM');
    apply_business_partner_type(platform_select.val());
    platform_select.bind('change', function(){apply_business_partner_type($(this).val())});
    $('#PAYU_EPAYMENT_IPN').bind('change', function() {
        if ($(this).val() == 1) {
            $('#EPAYMENT_IPN').show();
        } else {
            $('#EPAYMENT_IPN').hide();
        }
    });

    $('#PAYU_PAYMENT_BUTTON').bind('change', function() {
        $('#PAYU_PAYMENT_BUTTON_PREVIEW').attr('src', $(this).val());
    });
    $('#PAYU_PAYMENT_ADVERT').bind('change', function() {
        $('#PAYU_PAYMENT_ADVERT_PREVIEW').attr('src', $(this).val());
    });
});
