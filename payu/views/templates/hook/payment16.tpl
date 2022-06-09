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
    {if $showCardPayment == true}
        {if $showWidget}
			<div class="row">
				<div class="col-xs-12">
					<p class="payment_module">
						<a class="payu payu_card payment_open" data-payment="card" href=""
						   title="{l s='Pay by card' mod='payu'}">
                            {l s='Pay by card' mod='payu'}
						</a>
					</p>

					<div class="payment_module_content" style="display:none" data-payment-open="card">
                        {include file=$payCardTemplate}
					</div>

				</div>
			</div>
        {else}
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
    {/if}
    {if $showBlikPayment == true}
		<div class="row">
			<div class="col-xs-12">
				<p class="payment_module">
					<a class="payu payu_blik" href="{$blikActionUrl|escape:'htmlall':'UTF-8'}"
					   title="{l s='Pay by BLIK' mod='payu'}">
                        {l s='Pay by BLIK' mod='payu'}
					</a>
				</p>
			</div>
		</div>
    {/if}
    {if $paymentGrid == true}
		<div class="row">
			<div class="col-xs-12">
				<p class="payment_module payment_payu">
					<a class="payu payment_open payu_main" data-payment="transfer"
					   title="{l s='Pay by online transfer' mod='payu'}">
                        {l s='Pay by online transfer' mod='payu'}
					</a>
				</p>
				<div class="payment_module_content" style="display:none" data-payment-open="transfer">
                    {include file="$modulePath/views/templates/hook/paymentTransferList.tpl"}
				</div>

			</div>
		</div>
    {else}
		<div class="row">
			<div class="col-xs-12">
				<p class="payment_module payment_payu">
                    {if $showCardPayment == true}
						<a class="payu payu_main" href="{$actionUrl|escape:'htmlall':'UTF-8'}"
						   title="{l s='Pay by online transfer' mod='payu'}">
                            {l s='Pay by online transfer' mod='payu'}
						</a>
                    {else}
						<a class="payu payu_main" href="{$actionUrl|escape:'htmlall':'UTF-8'}"
						   title="{l s='Pay by online transfer or card' mod='payu'}">
                            {l s='Pay by online transfer or card' mod='payu'}
						</a>
                    {/if}
				</p>
			</div>
		</div>
    {/if}

    {if $payu_later_twisto_available == true}
		<div class="row">
			<div class="col-xs-12">
				<div class="payu-payment-credit-later-twisto-tile"
				     onclick="location.href='{$creditPayLaterTwistoActionUrl|escape:'htmlall':'UTF-8'}'"
				     title="{l s='Pay later with Twisto' mod='payu'}">
                    {l s='Pay later with Twisto' mod='payu'}
				</div>
			</div>
		</div>
    {/if}

    {if $credit_available == true}
		<div class="row">
			<div class="col-xs-12">
				<div class="payu-payment-credit-installment-tile"
				     onclick="location.href='{$creditActionUrl|escape:'htmlall':'UTF-8'}'"
				     title="{l s='Pay online in installments' mod='payu'}">
                    {l s='Pay online in installments' mod='payu'}
					<span id="payu-installment-cart-summary" class="payu-installment-cart-summary"></span>
					<script type="text/javascript" class="payu-script-tag">
						document.addEventListener("DOMContentLoaded", function (event) {
							var options = {
								creditAmount: {$cart_total_amount|floatval},
								posId: '{$credit_pos}',
								key: '{$credit_pos_key}',
								showLongDescription: true
							};
							window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary', options);
						});
						if (document.getElementById("payu-installment-cart-summary").childNodes.length == 0 &&
							typeof openpayu !== 'undefined' &&
							openpayu != null) {
							var options = {
								creditAmount: {$cart_total_amount|floatval},
								posId: '{$credit_pos}',
								key: '{$credit_pos_key}',
								showLongDescription: true
							};
							window.OpenPayU.Installments.miniInstallment('#payu-installment-cart-summary', options);
						}
					</script>
				</div>
			</div>
		</div>
    {/if}
</fieldset>
