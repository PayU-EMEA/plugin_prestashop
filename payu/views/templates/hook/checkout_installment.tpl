<img style="width: 121px" src="modules/payu/img/payu_installment.png" />
<br />
<span>Rata już od: <span id='payu-installments-mini-cart'></span></spaan>
<script type='text/javascript'>
    document.addEventListener("DOMContentLoaded", function(event) {
        openpayu.options.creditAmount ={$total_price};
        OpenPayU.Installments.miniInstallment('#payu-installments-mini-cart');
    });
</script>