{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018-2025 PayU
 *
 * http://www.payu.com
*}
<span class="payu-installment-price-listing">
        <span style="display: block;" class="payu-installment-mini-{$product_id|md5}"></span>
</span>
<script type="text/javascript" class="payu-script-tag">
    document.addEventListener("DOMContentLoaded", function (event) {
        $(".products").find(".payu-installment-price-listing").parent().css("margin-top", "-7px");
        $(".products").find(".payu-installment-price-listing").parent().prev().css("margin-top", "7px");
        $(".products").find(".payu-installment-price-listing > span").css("margin-top", "-2px");
        var options = {
            creditAmount:  {$product_price|floatval},
            posId: '{$credit_pos}',
            key: '{$credit_pos_key}',
            showLongDescription: true,
{if isset($credit_widget_currency_code)}            currencySign: '{$credit_widget_currency_code}',{"\n"}{/if}
{if isset($credit_widget_lang)}            lang: '{$credit_widget_lang}',{"\n"}{/if}
            excludedPaytypes: {$credit_widget_excluded_paytypes|@json_encode nofilter}
        };
        window.OpenPayU?.Installments?.miniInstallment('.payu-installment-mini-{$product_id|md5}', options);
    });
    if (typeof window.OpenPayU !== 'undefined') {
        var options = {
            creditAmount:  {$product_price|floatval},
            posId: '{$credit_pos}',
            key: '{$credit_pos_key}',
            showLongDescription: true,
{if isset($credit_widget_currency_code)}            currencySign: '{$credit_widget_currency_code}',{"\n"}{/if}
{if isset($credit_widget_lang)}            lang: '{$credit_widget_lang}',{"\n"}{/if}
            excludedPaytypes: {$credit_widget_excluded_paytypes|@json_encode nofilter}
        };
        window.OpenPayU?.Installments?.miniInstallment('.payu-installment-mini-{$product_id|md5}', options);
    }
</script>