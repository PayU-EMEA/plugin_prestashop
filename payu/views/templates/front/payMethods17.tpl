{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2016 PayU
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
            {l s="The administrator of your personal data within the meaning of the Personal Data Protection Act of 29 August 1997 (Journal of Laws of 2002, No. 101, item 926 as amended) is PayU SA with the registered office in Poznań (60-166) at ul. Grunwaldzka 182. Your personal data will be processed according to the applicable provisions of law for archiving and service provision purposes. Your data will not be made available to other entities, except of entities authorized by law. You are entitled to access and edit your data. Data provision is voluntary but required to achieve the above-mentioned purposes." mod='payu'}
        </div>

    {/if}
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="label" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="material-icons">chevron_left</i>{l s='Other payment methods' mod='payu'}
        </a>
        {if !isset($payMethods.error)}
            <button class="btn btn-primary pull-xs-right continue" type="submit">
                <span>{l s='I confirm my order' mod='payu'}<i class="icon-chevron-right right"></i></span>
            </button>
        {/if}
    </p>

</form>

{/block}