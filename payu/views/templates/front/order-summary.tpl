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
{capture name=path}{l s='Payment' mod='payu'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<form action="{$url_address|escape:'htmlall':'UTF-8'}" method="post" id="payu_form">
    <input type="hidden" name="redirectUri" value="{$redirectUri|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="sessionId" value="{$sessionId|escape:'htmlall':'UTF-8'}">
{l s='You selected Pay with PayU. Click the Confirm order button to go to the PayU site.' mod='payu'}
    <p class="cart_navigation">
        {if $id_customer > 0}
            <a href="{$base_dir_ssl|escape:'htmlall':'UTF-8'}{$return_page}" class="button_large">{l s='Back to the payment methods' mod='payu'}</a>
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