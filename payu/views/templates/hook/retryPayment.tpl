{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{*<div class="row repayment-container" id="payuRetryPayment">*}
{*	<div class="col-xs-12">*}
{*		<p class="payment_module">*}
{*			<a class="payu" href="{$payuActionUrl|escape:'htmlall':'UTF-8'}" title="{l s='Retry pay with PayU' mod='payu'}">*}
{*				<img src="{$payuImage|escape:'htmlall':'UTF-8'}" alt="{l s='Retry pay with PayU' mod='payu'}" />*}
{*				{l s='Retry pay with PayU' mod='payu'}*}
{*			</a>*}
{*		</p>*}
{*	</div>*}
{*</div>*}
<div class="box repayment-container" id="payuRetryPayment">
	{if $repaymentError}
		<div id="transfer-response-box" class="alert alert-warning" style="margin-bottom: 10px;">
			{l s='Select a payment channel' mod='payu'}
		</div>
	{/if}
	<form method="post" action="{$payuPayAction|escape:'html'}" class="repayment-options has-grid-{$grid} {if $has_sf} has-sf {/if} ">
		<input type="hidden" name="payuPay" value="1" />
		<input type="hidden" name="id_order" value="{$params['id_order']}" />
		<input type="hidden" name="order_reference" value="{$params['order_reference']}" />
		<input type="hidden" name="cardToken" value="" id="card-token"/>
		{foreach $gateways as $gateway}
			<div>
				<div id="payment-option-{$gateway@iteration}-container" class="{*payment-option*} clearfix repayment-single">
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
		<input type="hidden" name="transfer_gateway1" />
		<input type="submit" value="Zapłać z PayU" />
	</form>
</div>
<script>
	$(document).ready(function() {
		$('#payuRetryPayment').insertAfter($('.info-order').first());
		{if $repaymentError}
		if($('#transfer-response-box').length > 0) {
			$('.additional-information').each(function(){
				if($(this).find('[data-pm="transfer"]').length > 0){
					$(this).prepend($('#transfer-response-box'));
					$('#transfer-response-box').show();
					var th = $(this);
					setTimeout(function(){
						th.prev('div').find('.repayment-single img, input').click().trigger('click');
					}, 1000)
				}
			});
		}
		{/if}
	});

</script>

