{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $credit_available == true }
<span class="payu-installment-price-listing">
        <span id="payu-installment-mini-{$product_id}"></span>
</span>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function(event) {
        $(".payu-installment-price-listing").parent().css("margin-top","-7px");
        $(".payu-installment-price-listing").parent().prev().css("margin-top","9px");
        $(".payu-installment-price-listing > span").css("margin-top","-5px");
        if($('#payu-installment-mini-{$product_id}_installment-mini-details').length == 0) {
            openpayu.options.creditAmount ={$product_price|floatval};
            openpayu.options.showLongDescription = true;
            openpayu.options.lang = 'pl';
            OpenPayU.Installments.miniInstallment('#payu-installment-mini-{$product_id}');
        }
    });
</script>
{/if}
