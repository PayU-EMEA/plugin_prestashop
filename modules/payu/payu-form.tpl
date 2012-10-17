{*
*	ver. 0.1.2
*	PayU Payment Modules
*	
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
{capture name=path}{l s='Shipping' mod='payu'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='payu'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='The choice of method of payment' mod='payu'}</h3>

<form action="{$authUrl}" method="get" id="payu_form">
<input type="hidden" name="sessionId" value="{$sessionId}">
<input type="hidden" name="oauth_token" value="{$oauthToken}">
<input type="hidden" name="lang" value="{$langCode}">
<p class="cart_navigation">
	<a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Back to payment methods' mod='payu'}</a>
	<input type="submit" name="submit" value="{l s='Pay with PayU' mod='payu'}" class="exclusive_large" />
</p>
</form>