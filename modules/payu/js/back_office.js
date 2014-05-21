/*
 * 2007-2013 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2013 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
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
