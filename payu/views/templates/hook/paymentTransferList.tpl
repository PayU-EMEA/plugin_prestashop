{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}

<span class="payment-name" data-pm="transfer"></span>
{if $retryPayment && isset($payuNotifications.transfer)}
	<div id="transfer-response-box" class="alert alert-warning" style="margin-bottom: 10px;">
		{foreach $payuNotifications.transfer as $error}
			{$error}
			<br>
		{/foreach}
	</div>
{/if}

<form id="paymentTransfer" action="{$payuPayAction|escape:'html'}" class="pay-form-grid">
	<input type="hidden" name="payment_id">
	<input type="hidden" name="transferGateway">
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

	<div class="pay-transfer-accept">
		<button type="submit" disabled="" style="margin: 0 auto;display: table; float:none"
		        class="button btn btn-default button-medium center-block">
			<span>
				{l s='Place your order' mod='payu'}
				<i class="icon-chevron-right right"></i>
			</span>
		</button>
	</div>

</form>
{include file="$conditionTemplate"}

<script>
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
