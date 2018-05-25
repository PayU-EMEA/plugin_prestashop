{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{capture name=path}{l s='Pay with PayU' mod='payu'}{/capture}

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


<form action="{$payuPayAction|escape:'html'}" method="post" id="payuForm">
    <input type="hidden" name="payuPay" value="1" />

    {if isset($payMethods.error)}
        <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
    {else}
        <div id="payMethods">
            {foreach $payMethods.payByLinks as $payByLink}
                <div class="payMethod {if $payByLink->status != 'ENABLED'}payMethodDisable{else}payMethodEnable{/if} {if $payMethod == $payByLink->value}payMethodActive{/if}">
                    {if $payByLink->status == 'ENABLED'}
                        <input id="payMethod-{$payByLink->value}" type="radio" value="{$payByLink->value}" name="payMethod" {if $payMethod == $payByLink->value}checked="checked"{/if}>
                    {/if}
                    <label for="payMethod-{$payByLink->value}" class="payMethodLabel">
                        <div class="payMethodImage"><img src="{$payByLink->brandImageUrl}" alt="{$payByLink->name}"></div>
                        {$payByLink->name}
                    </label>
                </div>
            {/foreach}
        </div>

        <div class="payuConditions checkbox">
            <strong>{l s='Payment order' mod='payu'}</strong>:<br>
            {l s="Payment is processed by PayU SA; The recipient's data, the payment title and the amount are provided to PayU SA by the recipient; The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees." mod='payu'}
            <div class="checkbox">
                <input type="checkbox" value="1" {if $payuConditions}checked="checked"{/if} name="payuConditions" id="payuCondition">
                <label for="payuCondition">
                    {l s='I accept' mod='payu'} <a target="_blank" href="{$conditionUrl}">{l s='Terms of single PayU payment transaction' mod='payu'}</a>
                </label>
            </div>
            {l s='The controller of your personal data is PayU S.A. with its registered office in Poznan (60-166), at Grunwaldzka Street 182 ("PayU"). Your personal data will be processed for purposes of processing  payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.' mod='payu'}
            <br />
            {l s='The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.' mod='payu'}
            <br />
            {l s='You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click' mod='payu'} <a href="{l s='https://static.payu.com/sites/terms/files/payu_privacy_policy_en_en.pdf' mod='payu'}" target="_blank">{l s='Payu Privacy Policy' mod='payu'}</a>.
        </div>

    {/if}
    <p class="cart_navigation clearfix" id="cart_navigation">
        {if !$retryPayment}
        <a class="button-exclusive btn btn-default button_large" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='payu'}
        </a>
        {/if}
        {if !isset($payMethods.error)}
            <button class="button btn btn-default button-medium" type="submit">
                <span>{if !$retryPayment}{l s='I confirm my order' mod='payu'}{else}{l s='Pay' mod='payu'}{/if}<i class="icon-chevron-right right"></i></span>
            </button>
        {/if}
    </p>

</form>

