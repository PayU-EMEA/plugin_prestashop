{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<div class="payu-installment-panel">
    <span id="payu-installment-cart-total"></span>
    <script type="text/javascript" class="payu-script-tag">
        document.addEventListener("DOMContentLoaded", function (event) {
            openpayu.options.creditAmount ={$cart_total_amount|floatval};
            openpayu.options.showLongDescription = true;
            openpayu.options.lang = 'pl';
            OpenPayU.Installments.miniInstallment('#payu-installment-cart-total');
        });

    </script>
</div>
<hr class="separator payu-separator-reset">
<p></p>