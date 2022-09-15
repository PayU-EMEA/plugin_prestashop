{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<div class="box repayment-container" id="payuRetryPayment17">
	{if isset($payuNotifications.error)}
		<div class="alert alert-warning" style="margin-bottom: 10px;">
			{foreach $payuNotifications.error as $error}
				{$error}
				<br>
			{/foreach}
		</div>
	{/if}
	<form method="post" action="{$payuPayAction|escape:'html'}" class="repayment-options">
		<input type="hidden" name="id_order" value="{$params['id_order']}" />
		<input type="hidden" name="order_reference" value="{$params['order_reference']}" />
		{foreach $gateways as $gateway}
			<div>
				<div id="payment-option-{$gateway@iteration}-container" class="clearfix repayment-single">
					<span class="custom-radio float-xs-left">
						<input  class="ps-shown-by-js " id="payment-option-{$gateway@iteration}" data-module-name="payu" name="payment-option" type="radio" required="">
						<span></span>
					</span>
					<label for="payment-option-{$gateway@iteration}">
						<span>{$gateway->getCallToActionText()}</span>
						<img src="{$gateway->getLogo()}">
					</label>
				</div>
			</div>
			{if $gateway->getAdditionalInformation()}
				<div id="payment-option-{$gateway@iteration}-additional-information" class="js-additional-information definition-list additional-information ps-hidden">
					{$gateway->getAdditionalInformation() nofilter}
				</div>
			{/if}
		{/foreach}

		<input type="hidden" name="payMethod" value="" />
		<input type="hidden" name="transferGateway" />
		<input type="submit" value="Zapłać z PayU" />
	</form>
</div>
<script>
	{if $paymentId}
	document.addEventListener("DOMContentLoaded", function () {
		openPayment({$paymentId});
	});
	{/if}
</script>
