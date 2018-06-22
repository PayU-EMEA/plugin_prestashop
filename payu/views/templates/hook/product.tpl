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
        openpayu.options.creditAmount ={$product_price|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('#payu-installment-mini-{$product_id}');
    });
    if (document.getElementById("payu-installment-mini-{$product_id}").childNodes.length != 0 &&
        typeof openpayu !== 'undefined' &&
        openpayu != null) {
        openpayu.options.creditAmount ={$product_price|floatval};
        openpayu.options.showLongDescription = true;
        openpayu.options.lang = 'pl';
        OpenPayU.Installments.miniInstallment('#payu-installment-mini-{$product_id}');
    }
</script>
{/if}
