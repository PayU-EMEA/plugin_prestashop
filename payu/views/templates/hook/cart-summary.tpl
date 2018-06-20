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
    <div class="payu-installment-panel" >
            <span>
                Rata już od:
            </span>
            <span id="payu-installment-cart-total"></span>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function (event) {
                    openpayu.options.creditAmount ={$cart_total_amount|floatval};
                    OpenPayU.Installments.miniInstallment('#payu-installment-cart-total');
                });

            </script>
    </div>
    <hr class="separator payu-separator-reset" >
    <p></p>
{/if}