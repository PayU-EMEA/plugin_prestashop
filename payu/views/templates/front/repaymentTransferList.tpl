{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<span class="payu-marker-class"></span><span class="payment-name" data-pm="transfer"></span>

<div id="transfer-response-box" class="alert alert-warning" style="display: none">
    {l s='Select a payment channel' mod='payu'}
</div>

<div id="paymentTransfer" action="{$payuPayAction|escape:'html'}" class="pay-form-grid">
    <input type="hidden" name="payment_id">
    <input type="hidden" name="transfer_gateway1">
    <input type="hidden" name="payMethod" value="transfer">
    <div class="pay-methods required">
        {foreach $paymentMethods as $payment}
            <div id="payMethodContainer-{$payment->value}" class="pay-methods__item payMethod
				{if $payment->status != 'ENABLED'}payMethodDisable{else}payMethodEnable{/if}

			    {if $separateBlik && $payment->value == 'blik'}
				    pay-methods__hide
			    {/if}

			    {if $separateCard && $payment->value == 'c'}
				    pay-methods__hide
			    {/if}

			    {if $separateTwisto && $payment->value == 'dpt'}
				    pay-methods__hide
			    {/if}
		        ">
                <div class="pay-methods__item-inner required">
                    {if $payment->status == 'ENABLED'}
                        <input id="payMethod-{$payment->value}" type="radio" value="{$payment->value}"
                               name="transfer_gateway_id">
                    {/if}
                    <label for="payMethod-{$payment->value}" class="pay-methods__label payMethodLabel">
                        <img class="pay-methods__img" src="{$payment->brandImageUrl}" alt="{$payment->name}">
                    </label>
                </div>
            </div>
        {/foreach}
    </div>

</div>
{include file="$conditionTemplate"}

<script>
    {*    {if $paymentId}*}
    {*	var paymentId = {$paymentId};*}
    {*    {else}*}
    var paymentId = 0;
    {*    {/if}*}

    document.addEventListener("DOMContentLoaded", function () {
        if (paymentId) {
            setTimeout(function () {
                $('body').find('#payment-option-' + paymentId).click();
            }, 500);
        }
    });

    (function () {
        var applePayAvailable;

        try {
            applePayAvailable = window.ApplePaySession && window.ApplePaySession.canMakePayments();
        } catch (e) {
            applePayAvailable = false;
        }

        var applePayContainer = document.getElementById('payMethodContainer-jp');

        if (applePayAvailable) {
            applePayContainer.style.display = 'block';
        } else {
            applePayContainer.parentNode.removeChild(applePayContainer);
        }
    })();
</script>
