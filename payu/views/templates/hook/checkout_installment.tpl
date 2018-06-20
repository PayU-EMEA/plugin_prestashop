<div style="margin-bottom: -10px;">
    <img style="width: 121px; margin-top:-20px;" src="modules/payu/img/payu_installment.png" />
    <p>
        <span>Rata już od: <span id='payu-installments-mini-cart'></span></span>
        <script type='text/javascript'>
            document.addEventListener("DOMContentLoaded", function(event) {
                openpayu.options.creditAmount ={$total_price};
                OpenPayU.Installments.miniInstallment('#payu-installments-mini-cart');
            });
        </script>
    </p>
</div>