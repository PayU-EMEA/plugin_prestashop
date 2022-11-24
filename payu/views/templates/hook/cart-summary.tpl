{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
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
                showLongDescription: true
            };
            window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary', options);
        });

    </script>
</div>
<hr class="separator payu-separator-reset">
<p></p>