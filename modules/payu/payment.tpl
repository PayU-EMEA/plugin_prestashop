{*
*	ver. 1.9.3
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

<form action="{$summaryUrl}" method="post" id="payu_form">
<input type="hidden" name="sessionId" value="{$sessionId}">
<input type="hidden" name="oauth_token" value="{$oauthToken}">
<input type="hidden" name="lang" value="{$langCode}">
{l s='You selected Pay with PayU. Click the Pay with PayU button to go to the PayU site.' mod='payu'}
    <p class="cart_navigation">
    {if $id_customer > 0}
        <a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Back to the payment methods' mod='payu'}</a>
    {/if}
        <input type="submit" name="submit" value="{l s='Pay with PayU' mod='payu'}" class="exclusive_large" />
    </p>
</form>