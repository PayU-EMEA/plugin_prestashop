{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="payu" href="{$actionUrl|escape:'htmlall':'UTF-8'}"
               title="{l s='Pay by online transfer or card' mod='payu'}">
                <img class="payu-pay-image-16"
                     src="{$image|escape:'htmlall':'UTF-8'}"
                     alt="{l s='Pay by online transfer or card'
                     mod='payu'}"/>
                {l s='Pay by online transfer or card' mod='payu'}
            </a>
        </p>
    </div>
</div>
{if $credit_available == true}
    <div class="row">
        <div class="col-xs-12">
            <div class="payu-payment-credit-installment-tile" onclick="location.href='{$creditActionUrl|escape:'htmlall':'UTF-8'}'"
                 title="{l s='Pay online in installments' mod='payu'}">
                {l s='Pay online in installments' mod='payu'}
                <span id="payu-installment-cart-summary" class="payu-installment-cart-summary"></span>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function (event) {
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
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="payu-payment-credit-later-tile" onclick="location.href='{$creditPayULaterActionUrl|escape:'htmlall':'UTF-8'}'"
               title="{l s='Pay within 30 days with PayU' mod='payu'}">
                {l s='Pay within 30 days with PayU' mod='payu'}
                <span id="payu-later-cart-summary" class="payu-later-cart-summary"></span>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function (event) {
                        openpayu.options.amount ={$cart_total_amount|floatval};
                        openpayu.options.customElement = false;
                        openpayu.options.lang = 'pl';
                        OpenPayU.DelayedPayment.miniDelayedPayment('#payu-later-cart-summary');
                    });
                    if (document.getElementById("payu-later-cart-summary").childNodes.length != 0 &&
                        typeof openpayu !== 'undefined' &&
                        openpayu != null) {
                        openpayu.options.amount ={$cart_total_amount|floatval};
                        openpayu.options.customElement = false;
                        openpayu.options.lang = 'pl';
                        OpenPayU.DelayedPayment.miniDelayedPayment('#payu-later-cart-summary');
                    }
                </script>
            </div>
        </div>
    </div>
{/if}
