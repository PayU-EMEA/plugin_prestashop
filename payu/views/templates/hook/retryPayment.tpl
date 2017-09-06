{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<div class="row" id="payuRetryPayment">
	<div class="col-xs-12">
		<p class="payment_module">
			<a class="payu" href="{$payuActionUrl|escape:'htmlall':'UTF-8'}" title="{l s='Retry pay with PayU' mod='payu'}">
				<img src="{$payuImage|escape:'htmlall':'UTF-8'}" alt="{l s='Retry pay with PayU' mod='payu'}" />
				{l s='Retry pay with PayU' mod='payu'}
			</a>
		</p>
	</div>
</div>
<script>
	$(document).ready(function() {
		$('#payuRetryPayment').insertAfter($('.info-order').first());
	});
</script>

