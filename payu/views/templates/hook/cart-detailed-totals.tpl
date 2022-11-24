{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 *
 * http://www.payu.com
*}
<span style="display: block; margin-top: 10px;">
        <span id="payu-installment-cart-summary"></span>
    </span>
<script type="text/javascript" class="payu-script-tag">
    document.addEventListener("DOMContentLoaded", function (event) {
        var options = {
            creditAmount: {$cart_total_amount|floatval},
            posId: '{$credit_pos}',
            key: '{$credit_pos_key}',
            showLongDescription: true
        };
        window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary', options);
    });
    if (document.getElementById("payu-installment-cart-summary").childNodes.length == 0 &&
        typeof openpayu !== 'undefined' &&
        openpayu != null) {
        var options = {
            creditAmount: {$cart_total_amount|floatval},
            posId: '{$credit_pos}',
            key: '{$credit_pos_key}',
            showLongDescription: true
        };
        window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary', options);
    }
</script>
