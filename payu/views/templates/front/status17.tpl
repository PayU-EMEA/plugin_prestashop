{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2017 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{extends file=$layout}

{block name='content'}

    <section id="main">
        <div class="text-xs-center">
            <img src="{$payuLogo}" id="payuStatusLogo"> {l s='Thanks for choosing PayU payment' mod='payu'}
            <h2 style="margin: 30px 0">
                {l s='Order status' mod='payu'} {$orderPublicId} - {$orderStatus} <br/>
            </h2>

            {$HOOK_ORDER_CONFIRMATION}
            {$HOOK_PAYMENT_RETURN}

            <p class="cart_navigation">
                <a class="btn btn-primary" href="{$redirectUrl}">
                    {l s='Order details' mod='payu'}
                </a>
            </p>
        </div>
    </section>

{/block}