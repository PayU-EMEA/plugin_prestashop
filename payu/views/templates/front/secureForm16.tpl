{*
 * @author    PayU
 * @copyright Copyright (c) PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js"></script>
<script type="text/javascript" src="{$jsSdk}"></script>
<span class="payment-name" data-pm="card"></span>
{if	$retryPayment && isset($payuNotifications.card)}
	<div id="transfer-response-box" class="alert alert-warning" style="margin-bottom: 10px;">
		{foreach $payuNotifications.card as $error}
			{$error}
			<br>
		{/foreach}
	</div>
{/if}
<section id="main">
    {if isset($payuErrors) && $payuErrors|@count}
		<div class="alert alert-warning">
            {foreach $payuErrors as $error}
                {$error}
				<br>
            {/foreach}
		</div>
    {/if}

	<section id="content" class="page-content page-cms">
		{if !$retryPayment}
		<form action="{$payuPayAction|escape:'html'}" method="post" id="payu-card-form">
			<input type="hidden" name="payment_id" value="">
			<input type="hidden" name="payMethod" value="card"/>
			<input type="hidden" name="cardToken" value="" id="card-token"/>
			<div id="card-form-container">
                {if isset($payMethods.error)}
					<h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
                {else}
					<div id="payMethods" style="padding-bottom: 5px">
						<div id="response-box" class="alert alert-warning"
						     style="display: none; margin-bottom: 10px"></div>
                        {include file="$modulePath/views/templates/front/payuCardForm.tpl"}
					</div>
                    {include file="$modulePath/views/templates/front/conditions17.tpl"}
                {/if}

				<p class="clearfix" id="cart_navigation" style="margin:24px 0">
                    {if !isset($payMethods.error)}
						<button class="button btn btn-default button-medium center-block"
						        style="margin: 0 auto;display: table; float:none" type="submit" id="secure-form-pay">
                            <span>
                                {if !$retryPayment}
                                    {l s='I confirm my order' mod='payu'}
                                {else}
                                    {l s='Pay' mod='payu'}
                                {/if}
                                <i class="icon-chevron-right right"></i>
                            </span>
						</button>
                    {/if}
				</p>

			</div>
			<div id="waiting-box" class="wb-16" style="display: none">{l s='Please wait' mod='payu'}...</div>
		</form>
		{else}
			<div id="payu-card-form">
				<input type="hidden" name="payment_id" value="">
				<input type="hidden" name="payMethod" value="card"/>
				<input type="hidden" name="cardToken" value="" id="card-token"/>
				<div id="card-form-container">
					{if isset($payMethods.error)}
						<h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
					{else}
						<div id="payMethods" style="padding-bottom: 5px">
							<div id="response-box" class="alert alert-warning"
								 style="display: none; margin-bottom: 10px"></div>
							{include file="$modulePath/views/templates/front/payuCardForm.tpl"}
						</div>
						{include file="$modulePath/views/templates/front/conditions17.tpl"}
					{/if}
				</div>
				<div id="waiting-box" class="wb-16" style="display: none">{l s='Please wait' mod='payu'}...</div>
			</div>
		{/if}
	</section>

    {include file="$modulePath/views/templates/front/secureFormJs.tpl"}
</section>
