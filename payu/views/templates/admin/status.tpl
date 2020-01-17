{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2017 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $PAYU_PAYMENT_ACCEPT}
    {if $IS_17}
    <div class="row"><div class="col-lg-12">
    {/if}
    <br />
    <div id="payu-status-wrapper" class="panel">

        <form id="_form" class="form-horizontal hidden-print" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}logo.gif" alt="" /> {l s='PayU payment acceptance' mod='payu'}</legend>
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
                    {l s='Works only when the PayU order status is WAITING_FOR_CONFIRMATION or REJECTED' mod='payu'}
                </p>
            </fieldset>
        </form>
    </div>
    {if $IS_17}
    </div></div>
    {/if}
{/if}

{if $IS_17}
<div class="row"><div class="col-lg-12">
{/if}
<div id="payuOrders" class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i>
        {l s='PayU Orders' mod='payu'}
    </div>

    {$PAYU_CANCEL_ORDER_MESSAGE}

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th><span class="title_box ">{l s='Create date' mod='payu'}</span></th>
                <th><span class="title_box ">{l s='Update date' mod='payu'}</span></th>
                <th><span class="title_box ">PayU - OrderId</span></th>
                <th><span class="title_box ">PayU - ExtOrderId</span></th>
                <th><span class="title_box ">Payu - {l s='Status' mod='payu'}</span></th>
                <th><span class="title_box ">{l s='Action' mod='payu'}</span></th>
            </tr>
            </thead>
            {if $PAYU_ORDERS}
            <tbody>
            {foreach from=$PAYU_ORDERS item=payuOrder}
            <tr>
                <td>{dateFormat date=$payuOrder.create_at full=true}</td>
                <td>{dateFormat date=$payuOrder.update_at full=true}</td>
                <td>{$payuOrder.id_session}</td>
                <td>{$payuOrder.ext_order_id}</td>
                <td>{$payuOrder.status}</td>
                <td>{if $payuOrder.status == 'NEW' || $payuOrder.status == 'PENDING'}<a class="btn btn-primary" href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$PAYU_ORDER_ID|intval}&amp;cancelPayuOrder={$payuOrder.id_session}">{l s='Cancel' mod='payu'}</a>{/if}</td>
            </tr>
            {/foreach}
            </tbody>
            {/if}
        </table>
    </div>
</div>
{if $IS_17}
</div></div>
{/if}
