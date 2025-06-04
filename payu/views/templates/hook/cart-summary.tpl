{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018-2025 PayU
 *
 * http://www.payu.com
*}
<div class="payu-installment-panel">
    <span id="payu-installment-cart-total"></span>
    <script type="text/javascript" class="payu-script-tag">
        document.addEventListener("DOMContentLoaded", function (event) {
            var options = {
                creditAmount: {$cart_total_amount|floatval},
                posId: '{$credit_pos}',
                key: '{$credit_pos_key}',
                showLongDescription: true,
{if isset($credit_widget_currency_code)}                currencySign: '{$credit_widget_currency_code}',{"\n"}{/if}
{if isset($credit_widget_lang)}                lang: '{$credit_widget_lang}',{"\n"}{/if}
                excludedPaytypes: {$credit_widget_excluded_paytypes|@json_encode nofilter}
            };
            window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-total', options);
        });

    </script>
</div>
<hr class="separator payu-separator-reset">
<p></p>