{capture name=path}{l s='Payment'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Payment' mod='payu'}</h2>

<form action="{$summaryUrl}" method="post" id="payu_form">
    <input type="hidden" name="sessionId" value="{$sessionId}">
    <input type="hidden" name="oauth_token" value="{$oauthToken}">
    <input type="hidden" name="lang" value="{$langCode}">
{l s='Wybrano metodę płatności "Płacę w payu.pl". Kliknij w przycisk aby przejść do serwisu PayU.' mod='payu'}
    <p class="cart_navigation">
    {if $id_customer > 0}
        <a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Powrót do metod płatności' mod='payu'}</a>
    {/if}
        <input type="submit" name="submit" value="{l s='Płacę w payu.pl' mod='payu'}" class="exclusive_large" />
    </p>
</form>