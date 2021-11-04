<div class="payu-marker-class payu-method-description payu-checkout-installment">
    <img style="width: 121px; margin-top:-20px;" src="{$payu_installment_img}" />
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
