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
        Rata juz od:
        <span id="payu-installment-mini-{$product_id}"></span>
</span>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function(event) {
        openpayu.options.creditAmount ={$product_price|floatval};
        OpenPayU.Installments.miniInstallment('#payu-installment-mini-{$product_id}');
    });
    if(openpayu) {
        openpayu.options.creditAmount ={$product_price|floatval};
        OpenPayU.Installments.miniInstallment('#payu-installment-mini-{$product_id}');
    }
</script>
{/if}
