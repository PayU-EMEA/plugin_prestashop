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