{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<fieldset class="payu-payment-fieldset-1-6">
    <legend class="payu-payment-legend-1-6">
        <span class='logo' />
    </legend>
    {if $showCardPayment == true}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a class="payu payu_card" href="{$cardActionUrl|escape:'htmlall':'UTF-8'}"
                   title="{l s='Pay by card' mod='payu'}">
                    {l s='Pay by card' mod='payu'}
                </a>
            </p>
        </div>
    </div>
    {/if}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module payment_payu">
                {if $showCardPayment == true}
                <a class="payu" href="{$actionUrl|escape:'htmlall':'UTF-8'}"
                   title="{l s='Pay by online transfer' mod='payu'}">
                    {l s='Pay by online transfer' mod='payu'}
                </a>
                {else}
                    <a class="payu" href="{$actionUrl|escape:'htmlall':'UTF-8'}"
                       title="{l s='Pay by online transfer or card' mod='payu'}">
                        {l s='Pay by online transfer or card' mod='payu'}
                    </a>
                {/if}
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
                    <script type="text/javascript" class="payu-script-tag" >
                        document.addEventListener("DOMContentLoaded", function (event) {
                            openpayu.options.creditAmount ={$cart_total_amount|floatval};
                            openpayu.options.showLongDescription = true;
                            openpayu.options.lang = 'pl';
                            OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');
                        });
                        if (document.getElementById("payu-installment-cart-summary").childNodes.length == 0 &&
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
    {/if}
    {if $payu_later_available == true}
        <div class="row">
            <div class="col-xs-12">
                <div class="payu-payment-credit-later-tile" onclick="location.href='{$creditPayULaterActionUrl|escape:'htmlall':'UTF-8'}'"
                   title="{l s='Pay later with PayU' mod='payu'}">
                    {l s='Pay later with PayU' mod='payu'}
                    <span id="payu-later-cart-summary" class="payu-later-cart-summary"></span>
                    <script type="text/javascript" class="payu-script-tag" >
                        document.addEventListener("DOMContentLoaded", function (event) {
                            var options = {
                                amount: {$cart_total_amount|floatval},
                                lang: 'pl'
                            };
                            DelayedPayment.miniDelayedPayment('#payu-later-cart-summary', options);
                        });
                        if (document.getElementById("payu-later-cart-summary").childNodes.length == 0) {
                            var options = {
                                amount: {$cart_total_amount|floatval},
                                lang: 'pl'
                             };
                            DelayedPayment.miniDelayedPayment('#payu-later-cart-summary', options);
                        }
                    </script>
                </div>
            </div>
        </div>
    {/if}
</fieldset>