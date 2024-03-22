{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
<span class="payment-name" data-pm="transfer"></span>
{if isset($payuNotifications.transfer)}
	<div id="transfer-response-box" class="alert alert-warning" style="margin-bottom: 10px;">
        {foreach $payuNotifications.transfer as $error}
            {$error}
			<br>
        {/foreach}
	</div>
{/if}

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

			    {if $separateKlarna && $payment->value == 'dpkl'}
				    pay-methods__hide
			    {/if}

			    {if $separatePaypo && $payment->value == 'dpp'}
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
{include file="$conditionTemplate"}

<script>
	document.addEventListener("DOMContentLoaded", function () {
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
		{if $paymentId}
			openPayment({$paymentId});
		{/if}
	});
</script>
