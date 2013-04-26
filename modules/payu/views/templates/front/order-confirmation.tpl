{*
*	ver. 1.9.9
*	PayU Payment Modules
*
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
{if $smarty.const._PS_VERSION_ < 1.5 && isset($use_mobile) && $use_mobile}
	{include file="$tpl_dir./modules/payu/views/templates/front/order-confirmation.tpl"}
{else}
	{capture name=path}{l s='Order confirmation'}{/capture}
	{include file="$tpl_dir./breadcrumb.tpl"}

	<h1>{l s='Order confirmation'}</h1>

	{assign var='current_step' value='payment'}
	{include file="$tpl_dir./order-steps.tpl"}

	{include file="$tpl_dir./errors.tpl"}

	{$HOOK_ORDER_CONFIRMATION}
	{$HOOK_PAYMENT_RETURN}

	<br />

	{if $order}
	<p>{l s='Total of the transaction (taxes incl.) :'} <span class="bold">{$price}</span></p>
	<p>{l s='Your order ID is :'} <span class="bold">{$order.id_order}</span></p>
	<p>{l s='Your PayPal transaction ID is :'} <span class="bold">{$order.id_transaction}</span></p>
	{/if}
	<br />

	{if $is_guest}
		<a href="{$link->getPageLink('guest-tracking.php', true)}?id_order={$order.id_order}" title="{l s='Follow my order'}" data-ajax="false"><img src="{$img_dir}icon/order.gif" alt="{l s='Follow my order'}" class="icon" /></a>
		<a href="{$link->getPageLink('guest-tracking.php', true)}?id_order={$order.id_order}" title="{l s='Follow my order'}" data-ajax="false">{l s='Follow my order'}</a>
	{else}
		<a href="{$link->getPageLink('history.php', true)}" title="{l s='Back to orders'}" data-ajax="false"><img src="{$img_dir}icon/order.gif" alt="{l s='Back to orders'}" class="icon" /></a>
		<a href="{$link->getPageLink('history.php', true)}" title="{l s='Back to orders'}" data-ajax="false">{l s='Back to orders'}</a>
	{/if}
{/if}
