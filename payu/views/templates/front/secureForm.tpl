{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{capture name=path}{l s='Pay with PayU' mod='payu'}{/capture}

<script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js"></script>
<script type="text/javascript" src="{$jsSdk}"></script>

<div class="clearfix">
    <h2 id="payuAmountInfo">{$payuOrderInfo}: <strong>
            {if $currency}{convertPriceWithCurrency price=$total currency=$orderCurrency}{else}{convertPrice price=$total}{/if}
        </strong>
        {l s='(tax incl.)' mod='payu'}
    </h2>
    <img src="{$image}" id="payuLogo">
</div>

{if $payuErrors|@count}
    <div class="alert alert-warning">
        {foreach $payuErrors as $error}
            {$error}<br>
        {/foreach}
    </div>
{/if}

<form action="{$payuPayAction|escape:'html'}" method="post" id="payu-card-form">
    <input type="hidden" name="payuPay" value="1" />
    <input type="hidden" name="payMethod" value="card" />
    <input type="hidden" name="cardToken" value="" id="card-token" />
    <div id="card-form-container">
        {if isset($payMethods.error)}
            <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
        {else}
            <div id="payMethods" style="padding-bottom: 5px">
                <div id="response-box" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>

                {include file="$payCardTemplate"}

            </div>
            {include file="$conditionTemplate"}
        {/if}

        <p class="cart_navigation clearfix" id="cart_navigation">
            {if !$retryPayment}
                <a class="button-exclusive btn btn-default button_large" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                    <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='payu'}
                </a>
            {/if}
            {if !isset($payMethods.error)}
                <button class="button btn btn-default button-medium" type="submit" id="secure-form-pay">
                    <span>{if !$retryPayment}{l s='I confirm my order' mod='payu'}{else}{l s='Pay' mod='payu'}{/if}<i class="icon-chevron-right right"></i></span>
                </button>
            {/if}
        </p>
    </div>
    <h2 id="waiting-box" style="display: none">{l s='Please wait' mod='payu'}...</h2>
</form>

{include file="$secureFormJsTemplate"}
