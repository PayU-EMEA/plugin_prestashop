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
			<a class="payu" href="{$actionUrl|escape:'htmlall':'UTF-8'}" title="{l s='Pay with PayU' mod='payu'}">
				<img src="{$image|escape:'htmlall':'UTF-8'}" alt="{l s='Pay with PayU' mod='payu'}" />
				{l s='Pay with PayU' mod='payu'}
			</a>
		</p>
	</div>
</div>
{if $credit_available == true}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			<a class="payu" href="{$creditActionUrl|escape:'htmlall':'UTF-8'}" title="{l s='Credit your order!' mod='payu'}">
				<img src="{$creditImage|escape:'htmlall':'UTF-8'}" alt="{l s='Credit your order!' mod='payu'}" />
                {l s='Credit your order!' mod='payu'}
				<span>
        Rata juz od:
        <span id="payu-installment-cart-summary"></span>
    		</span>
				<script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function(event) {
                        openpayu.options.creditAmount={$cart_total_amount|floatval};
                        OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary');
                    });
				</script>
			</a>
		</p>
	</div>
</div>
{/if}
