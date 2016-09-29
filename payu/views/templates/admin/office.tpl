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
    <form id="_form" class="defaultForm payu form-horizontal" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
        <fieldset id="fieldset_1">
            <legend>{l s='Integration method' mod='payu'}</legend>
            <label>{l s='Payment methods displayed on Presta checkout summary page' mod='payu'}</label>
            <div class="margin-form">
                <select name="PAYU_RETRIEVE" id="PAYU_RETRIEVE">
                    {foreach from=$PAYU_RETRIEVE_OPTIONS item=option}
                        <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_RETRIEVE}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="clear"></div>
            <label></label>
            <div class="margin-form">
                <input type="hidden" name="submitpayu" value="1" />
                <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
            </div>
            <div class="clear"></div>
        </fieldset>


        {foreach from=$currencies item=currency}
            <fieldset id="fieldset_{$currency.iso_code}">
                <legend>{l s='POS settings - currency:' mod='payu'} {$currency.name} ({$currency.iso_code})</legend>
                <label>{l s='POS ID' mod='payu'}:</label>
                <div class="margin-form">
                    <input type="text" name="PAYU_MC_POS_ID[{$currency.iso_code}]" id="PAYU_MC_POS_ID_{$currency.iso_code}" value="{$PAYU_MC_POS_ID[$currency.iso_code]|escape:'htmlall':'UTF-8'}" size="10">
                    <sup>*</sup>
                </div>
                <div class="clear"></div>
                <label>{l s='Second key (MD5)' mod='payu'}:</label>
                <div class="margin-form">
                    <input type="text" name="PAYU_MC_SIGNATURE_KEY[{$currency.iso_code}]" id="PAYU_MC_SIGNATURE_KEY_{$currency.iso_code}" value="{$PAYU_MC_SIGNATURE_KEY[$currency.iso_code]|escape:'htmlall':'UTF-8'}" size="32">
                    <sup>*</sup>
                </div>
                <div class="clear"></div>
                <label>{l s='OAuth - client_id' mod='payu'}:</label>
                <div class="margin-form">
                    <input type="text" name="PAYU_MC_OAUTH_CLIENT_ID[{$currency.iso_code}]" id="PAYU_MC_OAUTH_CLIENT_ID_{$currency.iso_code}" value="{$PAYU_MC_OAUTH_CLIENT_ID[$currency.iso_code]|escape:'htmlall':'UTF-8'}" size="10">
                    <sup>*</sup>
                </div>
                <div class="clear"></div>
                <label>{l s='OAuth - client_secret' mod='payu'}:</label>
                <div class="margin-form">
                    <input type="text" name="PAYU_MC_OAUTH_CLIENT_SECRET[{$currency.iso_code}]" id="PAYU_MC_OAUTH_CLIENT_SECRET_{$currency.iso_code}" value="{$PAYU_MC_OAUTH_CLIENT_SECRET[$currency.iso_code]|escape:'htmlall':'UTF-8'}" size="32">
                    <sup>*</sup>
                </div>
                <div class="clear"></div>
                <div class="small"><sup>*</sup> {l s='Required field' mod='payu'}</div>

                <div class="margin-form">
                    <input type="hidden" name="submitpayu" value="1" />
                    <input type="submit" class="button" name="submitButton" value="{l s='Save' mod='payu'}" />
                </div>
                <div class="clear"></div>
            </fieldset>
            <br>
        {/foreach}
        <fieldset id="fieldset_5">
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
