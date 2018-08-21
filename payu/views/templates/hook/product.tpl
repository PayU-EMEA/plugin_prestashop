{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<span class="payu-installment-price-listing">
        <span style="display: block;" class="payu-installment-mini-{$product_id|md5}"></span>
</span>
<script type="text/javascript" class="payu-script-tag">
    document.addEventListener("DOMContentLoaded", function (event) {
        $(".products").find(".payu-installment-price-listing").parent().css("margin-top", "-7px");
        $(".products").find(".payu-installment-price-listing").parent().prev().css("margin-top", "7px");
        $(".products").find(".payu-installment-price-listing > span").css("margin-top", "-2px");

        openpayu.options.creditAmount ={$product_price|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('.payu-installment-mini-{$product_id|md5}');
    });
    if (typeof openpayu !== 'undefined') {
        openpayu.options.creditAmount ={$product_price|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('.payu-installment-mini-{$product_id|md5}');
    }
</script>
