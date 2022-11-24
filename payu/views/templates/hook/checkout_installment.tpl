{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 *
 * http://www.payu.com
*}
<span class="payment-name" data-pm="ai"></span>
<div class="payu-method-description payu-checkout-installment">
    <p>
        <span id='payu-installments-mini-cart'></span>
        <script type='text/javascript' class="payu-script-tag" >
            document.addEventListener("DOMContentLoaded", function(event) {
                var options = {
                    creditAmount: {$total_price},
                    posId: '{$credit_pos}',
                    key: '{$credit_pos_key}',
                    showLongDescription: true
                };
                window.OpenPayU.Installments.miniInstallment('#payu-installments-mini-cart', options);
            });
        </script>
    </p>
    <p>
        {l s='Order will be done after positive decision' mod='payu'}
    </p>
</div>
<script type="text/javascript">
    (function () {
        window.payuPaymentLoaded = true;
    })();
</script>
