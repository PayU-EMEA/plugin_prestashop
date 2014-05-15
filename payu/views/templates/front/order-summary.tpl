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
{capture name=path}{l s='Payment' mod='payu'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<form action="{$url_address|escape:'htmlall':'UTF-8'}" method="post" id="payu_form">
    <input type="hidden" name="redirectUri" value="{$redirectUri|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="sessionId" value="{$sessionId|escape:'htmlall':'UTF-8'}">
{l s='You selected Pay with PayU. Click the Confirm order button to go to the PayU site.' mod='payu'}
    <p class="cart_navigation">
        {if $id_customer > 0}
            <a href="{$base_dir_ssl|escape:'htmlall':'UTF-8'}order.php?step=3" class="button_large">{l s='Back to the payment methods' mod='payu'}</a>
        {/if}
        <input type="submit" name="submit" value="{l s='Confirm order' mod='payu'}" class="exclusive_large" />
    </p>
</form>

{*

{literal}
<script type="text/javascript">
    
    function redirectToPayU(){
        window.location.replace("{/literal}{$redirectUri}{literal}".replace("&amp;","&"));
    }
    
</script>
{/literal}

<h2>{l s='Payment' mod='payu'}</h2>
{l s='You selected Pay with PayU. Click the Confirm order button to go to the PayU site.' mod='payu'}
<p class="cart_navigation">
    {if $id_customer > 0}
        <a href="{$base_dir_ssl|escape:'htmlall':'UTF-8'}order.php?step=3" class="button_large">{l s='Back to the payment methods' mod='payu'}</a>
    {/if}
    <input onclick="redirectToPayU();" id="payu_submit" type="submit" name="submit" value="{l s='Confirm order' mod='payu'}" class="exclusive_large" />
</p>

*}