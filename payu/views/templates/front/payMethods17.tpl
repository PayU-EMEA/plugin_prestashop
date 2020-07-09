{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{extends file=$layout}

{block name='content'}
    <div class="clearfix">
        <h2 id="payuAmountInfo">{$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}</h2>
        <img src="{$image}" id="payuLogo">
    </div>

    {if $payuErrors|@count}
        <div class="alert alert-warning">
            {foreach $payuErrors as $error}
                {$error|unescape:'html'}<br>
            {/foreach}
        </div>
    {/if}

    <form action="{$payuPayAction|escape:'html'}" method="post" id="payuForm">
        <input type="hidden" name="payuPay" value="1" />

        {if isset($payMethods.error)}
            <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
        {else}
            <div id="payMethods">
                {foreach $payMethods.payByLinks as $payByLink}
                    <div id="payMethodContainer-{$payByLink->value}" class="payMethod {if $payByLink->status != 'ENABLED'}payMethodDisable{else}payMethodEnable{/if} {if $payMethod == $payByLink->value}payMethodActive{/if}" {if $payByLink->value == 'jp'}style="display: none" {/if}>
                        {if $payByLink->status == 'ENABLED'}
                            <input id="payMethod-{$payByLink->value}" type="radio" value="{$payByLink->value}" name="payMethod" {if $payMethod == $payByLink->value}checked="checked"{/if}>
                        {/if}
                        <label for="payMethod-{$payByLink->value}" class="payMethodLabel" data-autosubmit="{$payByClick}">
                            <div class="payMethodImage" title="{$payByLink->name}"><img src="{$payByLink->brandImageUrl}" alt="{$payByLink->name}"></div>
                            {$payByLink->name}
                        </label>
                    </div>
                {/foreach}
            </div>

            {include file='module:payu/views/templates/front/conditions17.tpl'}
        {/if}

        <p class="cart_navigation clearfix" id="cart_navigation">
            {if !$retryPayment}
                <a class="label" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                    {l s='Other payment methods' mod='payu'}
                </a>
            {/if}
            {if !isset($payMethods.error)}
                <button class="btn btn-primary float-xs-right continue" type="submit">
                    <span>{if !$retryPayment}{l s='I confirm my order' mod='payu'}{else}{l s='Pay' mod='payu'}{/if}</span>
                </button>
            {/if}
        </p>

    </form>
    <script>
        (function () {
            var applePayAvailable;

            try {
                applePayAvailable = window.ApplePaySession && window.ApplePaySession.canMakePayments();
            } catch (e) {
                applePayAvailable = false;
            }

            var applePayContainer = document.getElementById('payMethodContainer-jp');

            if (applePayAvailable) {
                applePayContainer.style.display = 'block';
            } else {
                applePayContainer.parentNode.removeChild(applePayContainer);
            }
        })();
    </script>
{/block}