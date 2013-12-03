{*
*	ver. 1.9.11
*	PayU Payment Modules
*
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
<p>{l s='Your order on' mod='payu'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='payu'}
	<br /><br />
	{l s='You have chosen the PayU method.' mod='paypal'}
	<br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='paypal'}</span>
	<br /><br />{l s='For any questions or for further information, please contact our' mod='payu'}
	<a href="{$link->getPageLink('contact', true)}contact-form.php" data-ajax="false">{l s='customer support' mod='payu'}</a>.
</p>
