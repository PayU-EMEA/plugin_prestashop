{*
*	ver. 1.9.8
*	PayU Payment Modules
*	
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
{capture name=path}{l s='Shipping'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='payu'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Wybór metody płatności' mod='payu'}</h3>

<form action="{$summaryUrl}" method="get" id="payu_form">
<input type="hidden" name="sessionId" value="{$sessionId}">
	<input type="hidden" name="oauth_token" value="{$accessToken}">
<p class="cart_navigation">
	<input type="submit" name="submit" value="{l s='Pay with PayU' mod='payu'}" class="exclusive_large" />
</p>
</form>