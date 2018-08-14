{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<span style="display: block; margin-top: 10px;">
        <span id="payu-installment-cart-summary"></span>
    </span>
<script type="text/javascript" class="payu-script-tag">
    document.addEventListener("DOMContentLoaded", function (event) {
        openpayu.options.creditAmount ={$cart_total_amount|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');
    });
    if (document.getElementById("payu-installment-cart-summary").childNodes.length == 0 &&
        typeof openpayu !== 'undefined' &&
        openpayu != null) {
        openpayu.options.creditAmount ={$cart_total_amount|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');
    }
</script>
