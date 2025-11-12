{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
<div class="box repayment-container" id="payuRetryPayment">
	{if isset($payuNotifications.error)}
		<div class="alert alert-warning">
			{foreach $payuNotifications.error as $error}
				{$error}
				<br>
			{/foreach}
		</div>
	{/if}
	<form method="post" action="{$payuPayAction|escape:'html'}" class="repayment-options has-grid-{$grid} {if $has_sf} has-sf {/if} ">
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
						<span>{$gateway['CallToActionText']}</span>
						<img src="{$gateway['Logo']}">
					</label>
				</div>
			</div>
			{if $gateway['AdditionalInformation']}
				<div id="payment-option-{$gateway@iteration}-additional-information" class="js-additional-information definition-list additional-information ps-hidden">
					{$gateway['AdditionalInformation'] nofilter}
				</div>
			{/if}
		{/foreach}

		<input type="hidden" name="payMethod" value="" />
		<input type="hidden" name="transferGateway" />
		<input type="submit" value="{l s='Pay with PayU' mod='payu'}" />
	</form>
</div>

