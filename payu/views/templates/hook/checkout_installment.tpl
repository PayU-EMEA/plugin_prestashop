<div style="margin-bottom: -10px;">
    <img style="width: 121px; margin-top:-20px;" src="modules/payu/img/payu_installment.png" />
    <p>
        <span id='payu-installments-mini-cart'></span>
        <script type='text/javascript'>
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