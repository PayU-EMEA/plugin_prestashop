{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<div id="payu-wrapper">
<form id="_form" class="defaultForm payu" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
<fieldset id="fieldset_0">
    <legend>{l s='Platform setup' mod='payu'}</legend>
    <label>{l s='Business platform' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_PLATFORM" id="PAYU_PAYMENT_PLATFORM">
            {foreach from=$PAYU_PAYMENT_PLATFORM_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_PLATFORM}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>
</fieldset>
<br>
<fieldset id="fieldset_1" class="hide {$PAYU_PAYMENT_PLATFORM_PLATNOSCI|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Main settings' mod='payu'}</legend>
    <div class="clear"></div>
    <label>{l s='Self-Return Enabled' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_SELF_RETURN" id="PAYU_SELF_RETURN">
            {foreach from=$PAYU_SELF_RETURN_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_SELF_RETURN}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <label>{l s='Order validity time' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_VALIDITY_TIME" id="PAYU_VALIDITY_TIME">
            {foreach from=$PAYU_VALIDITY_TIME_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_VALIDITY_TIME}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <p class="preference_description">
            {l s='Value determines how long the order will be important in PayU.' mod='payu'}
        </p>
    </div>
    <div class="clear"></div>
    <label>{l s='One step checkout enabled' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_ONE_STEP_CHECKOUT" id="PAYU_ONE_STEP_CHECKOUT">
            {foreach from=$PAYU_ONE_STEP_CHECKOUT_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_ONE_STEP_CHECKOUT}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <p class="preference_description">
            {l s='Option is available only for logged customers.' mod='payu'}
        </p>
    </div>
    <div class="clear"></div>
    <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>
    <div class="clear"></div>
    <label></label>
    <div class="margin-form">
        <input type="hidden" name="submitpayu" value="1" />
        <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
    </div>
    <div class="clear"></div>
</fieldset>
<br>

<fieldset id="fieldset_3" class="hide {$PAYU_PAYMENT_PLATFORM_PLATNOSCI|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Environment settings' mod='payu'}</legend>
    <label>{l s='POS ID' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_POS_ID" id="PAYU_POS_ID" value="{$PAYU_POS_ID|escape:'htmlall':'UTF-8'}" size="10">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='Second key (MD5)' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SIGNATURE_KEY" id="PAYU_SIGNATURE_KEY" value="{$PAYU_SIGNATURE_KEY|escape:'htmlall':'UTF-8'}" size="32">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>
    <label></label>
    <div class="margin-form">
        <input type="hidden" name="submitpayu" value="1" />
        <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
    </div>
    <div class="clear"></div>
</fieldset>
<br>
<fieldset id="fieldset_4" class="hide {$PAYU_PAYMENT_PLATFORM_EPAYMENT|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Environment settings' mod='payu'}</legend>
    <label>{l s='Merchant' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_EPAYMENT_MERCHANT" id="PAYU_EPAYMENT_MERCHANT" value="{$PAYU_EPAYMENT_MERCHANT|escape:'htmlall':'UTF-8'}" size="10">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='Secret Key' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_EPAYMENT_SECRET_KEY" id="PAYU_EPAYMENT_SECRET_KEY" value="{$PAYU_EPAYMENT_SECRET_KEY|escape:'htmlall':'UTF-8'}" size="32">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='IPN' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_EPAYMENT_IPN" id="PAYU_EPAYMENT_IPN">
            {foreach from=$PAYU_EPAYMENT_IPN_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_EPAYMENT_IPN}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <div id="EPAYMENT_IPN" class="{if $PAYU_EPAYMENT_IPN=='0'}hide{/if}">
        <label>{l s='IPN URL' mod='payu'}</label>
        <div class="margin-form">
            <input type="text" readonly id="PAYU_EPAYMENT_IPN_URL" value="{$PAYU_EPAYMENT_IPN_URL|escape:'htmlall':'UTF-8'}" size="90">
        </div>
        <div class="clear"></div>
    </div>
    <label>{l s='IDN' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_EPAYMENT_IDN" id="PAYU_EPAYMENT_IDN">
            {foreach from=$PAYU_EPAYMENT_IDN_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_EPAYMENT_IDN}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='IRN' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_EPAYMENT_IRN" id="PAYU_EPAYMENT_IRN">
            {foreach from=$PAYU_EPAYMENT_IRN_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_EPAYMENT_IRN}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>
    <label></label>
    <div class="margin-form">
        <input type="hidden" name="submitpayu" value="1" />
        <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
    </div>
    <div class="clear"></div>
</fieldset>
<br>

<fieldset id="fieldset_5" class="hide {$PAYU_PAYMENT_PLATFORM_PLATNOSCI|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Payment statuses' mod='payu'}</legend>
    <label>{l s='Pending status' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_PENDING" id="PAYU_PAYMENT_STATUS_PENDING">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_PENDING}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <label>{l s='Waiting For Confirmation' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_SENT" id="PAYU_PAYMENT_STATUS_SENT">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_SENT}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <label>{l s='Complete status' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_COMPLETED" id="PAYU_PAYMENT_STATUS_COMPLETED">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_COMPLETED}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <label>{l s='Canceled status' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_CANCELED" id="PAYU_PAYMENT_STATUS_CANCELED">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_CANCELED}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <label>{l s='Rejected status' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_REJECTED" id="PAYU_PAYMENT_STATUS_REJECTED">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_REJECTED}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
    <div class="clear"></div>
    <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>
    <label></label>
    <div class="margin-form">
        <input type="hidden" name="submitpayu" value="1" />
        <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
    </div>
    <div class="clear"></div>
</fieldset>

</form>
</div>
