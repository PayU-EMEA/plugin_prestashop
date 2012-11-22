{if $smarty.const._PS_VERSION_ < 1.5 && isset($use_mobile) && $use_mobile}
	{include file="$tpl_dir./modules/payu/views/templates/front/error.tpl"}
{else}
	{capture name=path}<a href="order.php">{l s='Your shopping cart' mod='payu'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='PayU' mod='payu'}{/capture}
	{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='payu'}</h2>
	{if isset($message)}
		<div class="error">
			<p>{$message}</p>
			<p><a href="{$base_dir}" class="button_small" title="{l s='Back' mod='payu'}">&laquo; {l s='Back' mod='payu'}</a></p>
		</div>
	{/if}
{/if}