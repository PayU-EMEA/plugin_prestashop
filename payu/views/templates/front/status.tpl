{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2017 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{capture name=path}{l s='Pay with PayU' mod='payu'}{/capture}


<div class="box clearfix">
    <img src="{$payuLogo}"> {l s='Thanks for choosing PayU payment' mod='payu'}
    <h2 style="margin: 30px 0">
        {l s='Order status' mod='payu'} {$orderPublicId} - {$orderStatus} <br/>
    </h2>

    {$HOOK_ORDER_CONFIRMATION nofilter}
    {$HOOK_PAYMENT_RETURN nofilter}

    <a class="button btn btn-default button-medium pull-right" href="{$redirectUrl}">
        <span>
            {l s='Order details' mod='payu'}
            <i class="icon-chevron-right"></i>
        </span>
    </a>
</div>