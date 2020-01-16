<div class="payu-marker-class payu-method-description payu-checkout-installment">
    <img style="width: 121px; margin-top:-20px;" src="{$payu_installment_img}" />
    <p>
        <span id='payu-installments-mini-cart'></span>
        <script type='text/javascript' class="payu-script-tag" >
            document.addEventListener("DOMContentLoaded", function(event) {
                openpayu.options.creditAmount ={$total_price};
                openpayu.options.showLongDescription = true;
                openpayu.options.lang = 'pl';
                OpenPayU.Installments.miniInstallment('#payu-installments-mini-cart');
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
