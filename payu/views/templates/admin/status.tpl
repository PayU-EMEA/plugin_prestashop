{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
*}
<br />
<div id="payu-status-wrapper">
    <form id="_form" class="defaultForm status-payu" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.gif" alt="" />{l s='PayU payment acceptance' mod='payu'}</legend>
            <label>{l s='Choose status' mod='payu'}</label>
            <div class="margin-form">
                <select name="PAYU_PAYMENT_STATUS" id="PAYU_PAYMENT_STATUS">
                    {foreach from=$PAYU_PAYMENT_STATUS_OPTIONS item=option}
                        <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <input type="submit" class="button" name="submitpayustatus" value="{l s='Change' mod='payu'}" />
            </div>
            <p class="preference_description">
                {l s='Works only when the order status is PayU payment awaits for reception.' mod='payu'}
            </p>
            <div class="clear"></div>
        </fieldset>
    </form>
</div>