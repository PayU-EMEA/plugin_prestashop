{*
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
*}
{capture name=path}<a href="order.php">{l s='Your shopping cart' mod='payu'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='PayU' mod='payu'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='payu'}</h2>
{if isset($message)}
	<div class="error">
		<p>{$message|escape:'htmlall':'UTF-8'}</p>
		<p><a href="{$base_dir|escape:'htmlall':'UTF-8'}" class="button_small" title="{l s='Back' mod='payu'}">&laquo; {l s='Back' mod='payu'}</a></p>
	</div>
{/if}
