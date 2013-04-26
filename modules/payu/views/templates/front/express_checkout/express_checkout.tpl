{*
*	ver. 1.9.9
*	PayU Payment Modules
*
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
{if $cart->id_customer}
<div id="container_payu_express_checkout" style="float:right; margin: 10px 40px 0 0">
    <a href="{$checkout_url}" title="{l s='Pay with PayU' mod='payu'}">
        <img src="{$image}" alt="{l s='Pay with PayU' mod='payu'}" />
    </a>
</div>
<div class="clearfix"></div>
{/if}
