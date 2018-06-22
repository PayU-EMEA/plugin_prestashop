{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $credit_available == true && $cart_total_amount>0}
    <span>
        <span id="payu-installment-cart-summary"></span>
    </span>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function(event) {
            openpayu.options.creditAmount ={$cart_total_amount|floatval};
            openpayu.options.showLongDescription = true;
            openpayu.options.lang = 'pl';
            OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');
        });
        if (document.getElementById("payu-installment-cart-summary").childNodes.length != 0 &&
            typeof openpayu !== 'undefined' &&
            openpayu != null) {
			openpayu.options.creditAmount ={$cart_total_amount|floatval};
            openpayu.options.showLongDescription = true;
            openpayu.options.lang = 'pl';
			OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');  
		}
    </script>
{/if}
