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
document.addEventListener("DOMContentLoaded", function(event) {
    var payuIndexes = [];
    for (var i = 0; i < 20; ++i) {
        var isFound = $("#payment-option-" + i + "-additional-information .payu-marker-class").length > 0;
        if (isFound) {
            payuIndexes.push(i);
        }
    }
    if (payuIndexes.length > 0) {
        $(".payment-options").append("<fieldset id='payu-methods-grouped' class='payu-payment-fieldset-1-7'>" +
            "   <legend class='payu-payment-legend-1-7'>" +
            "        <span>" +
            "            <img height='30px' src='{$payu_logo_img}' />" +
            "        </span>" +
            "    </legend>" +
            "</fieldset>");
    }
    for (var indexOfPayuElement in payuIndexes) {
        var element1 = $("#payment-option-" + payuIndexes[indexOfPayuElement] + "-container").parent();
        var element2 = $("#payment-option-" + payuIndexes[indexOfPayuElement] + "-additional-information");
        var element3 = $("#pay-with-payment-option-" + payuIndexes[indexOfPayuElement] + "-form");
        element1.detach().appendTo('#payu-methods-grouped');
        element2.detach().appendTo('#payu-methods-grouped');
        element3.detach().appendTo('#payu-methods-grouped');
    }
});
</script>
