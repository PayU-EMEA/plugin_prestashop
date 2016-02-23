{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $PAYU_PAYMENT_ACCEPT}
    <br />
    <div id="payu-status-wrapper" class="panel">

        <form id="_form" class="form-horizontal hidden-print" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.gif" alt="" /> {l s='PayU payment acceptance' mod='payu'}</legend>
                <label>{l s='Choose status' mod='payu'}</label>
                <div class="form-group">
                    <div class="col-lg-9">
                        <select name="PAYU_PAYMENT_STATUS" id="PAYU_PAYMENT_STATUS">
                            {foreach from=$PAYU_PAYMENT_STATUS_OPTIONS item=option}
                                <option value="{$option.id|escape:'htmlall':'UTF-8'}" {if $option.id == $PAYU_PAYMENT_STATUS}selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <button class="btn btn-primary" name="submitpayustatus" type="submit">{l s='Change' mod='payu'}</button>
                    </div>
                </div>
                <p class="preference_description">
                    {l s='Works only when the order status is PayU payment awaits for reception.' mod='payu'}
                </p>

            </fieldset>
        </form>
    </div>
{/if}
