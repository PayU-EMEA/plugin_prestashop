{*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
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
    <label>{l s='Test Mode On' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_ENVIRONMENT" id="PAYU_ENVIRONMENT">
            {foreach from=$PAYU_ENVIRONMENT_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_ENVIRONMENT}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </div>
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
<fieldset id="fieldset_2" class="hide {$PAYU_PAYMENT_PLATFORM_PLATNOSCI|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Environment settings (Test)' mod='payu'}</legend>
    <label>{l s='POS ID' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SANDBOX_POS_ID" id="PAYU_SANDBOX_POS_ID" value="{$PAYU_SANDBOX_POS_ID|escape:'htmlall':'UTF-8'}" size="10">
    </div>
    <div class="clear"></div>
    <label>{l s='Key (MD5)' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SANDBOX_CLIENT_SECRET" id="PAYU_SANDBOX_CLIENT_SECRET" value="{$PAYU_SANDBOX_CLIENT_SECRET|escape:'htmlall':'UTF-8'}" size="32">
    </div>
    <div class="clear"></div>
    <label>{l s='Second key (MD5)' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SANDBOX_SIGNATURE_KEY" id="PAYU_SANDBOX_SIGNATURE_KEY" value="{$PAYU_SANDBOX_SIGNATURE_KEY|escape:'htmlall':'UTF-8'}" size="32">
    </div>
    <div class="clear"></div>
    <label>{l s='Pos Auth Key' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SANDBOX_POS_AUTH_KEY" id="PAYU_SANDBOX_POS_AUTH_KEY" value="{$PAYU_SANDBOX_POS_AUTH_KEY|escape:'htmlall':'UTF-8'}" size="10">
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
<fieldset id="fieldset_3" class="hide {$PAYU_PAYMENT_PLATFORM_PLATNOSCI|escape:'htmlall':'UTF-8'}">
    <legend>{l s='Environment settings' mod='payu'}</legend>
    <label>{l s='POS ID' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_POS_ID" id="PAYU_POS_ID" value="{$PAYU_POS_ID|escape:'htmlall':'UTF-8'}" size="10">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='Key (MD5)' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_CLIENT_SECRET" id="PAYU_CLIENT_SECRET" value="{$PAYU_CLIENT_SECRET|escape:'htmlall':'UTF-8'}" size="32">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='Second key (MD5)' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_SIGNATURE_KEY" id="PAYU_SIGNATURE_KEY" value="{$PAYU_SIGNATURE_KEY|escape:'htmlall':'UTF-8'}" size="32">
        <sup>*</sup>
    </div>
    <div class="clear"></div>
    <label>{l s='Pos Auth Key' mod='payu'}</label>
    <div class="margin-form">
        <input type="text" name="PAYU_POS_AUTH_KEY" id="PAYU_POS_AUTH_KEY" value="{$PAYU_POS_AUTH_KEY|escape:'htmlall':'UTF-8'}" size="10">
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
    <label>{l s='Sent status' mod='payu'}</label>
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
    <label>{l s='Delivered status' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_STATUS_DELIVERED" id="PAYU_PAYMENT_STATUS_DELIVERED">
            {foreach from=$PAYU_PAYMENT_STATES_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS_DELIVERED}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
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

<br>
<fieldset id="fieldset_6">
    <legend>{l s='External resources' mod='payu'}</legend>
    <label>{l s='Payment button' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_BUTTON" id="PAYU_PAYMENT_BUTTON">
            {foreach from=$PAYU_PAYMENT_BUTTON_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_BUTTON}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <div class="img-preview"><img id="PAYU_PAYMENT_BUTTON_PREVIEW" src="{$PAYU_PAYMENT_BUTTON|escape:'htmlall':'UTF-8'}" alt="" /></div>
    </div>
    <div class="clear"></div>
    <label>{l s='Payment advert' mod='payu'}</label>
    <div class="margin-form">
        <select name="PAYU_PAYMENT_ADVERT" id="PAYU_PAYMENT_ADVERT">
            {foreach from=$PAYU_PAYMENT_ADVERT_OPTIONS item=option}
                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_ADVERT}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <div class="img-preview"><img id="PAYU_PAYMENT_ADVERT_PREVIEW" src="{$PAYU_PAYMENT_ADVERT|escape:'htmlall':'UTF-8'}" alt="" /></div>
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
