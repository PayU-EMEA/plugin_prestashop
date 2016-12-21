{*
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
*}
{extends file=$layout}

{block name='content'}

<div class="clearfix">
	<h2 id="payuAmountInfo">{$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}</h2>
	<img src="{$image}" id="payuLogo">
</div>

{if isset($message)}
	<div class="alert alert-warning">
		<p>{$message|escape:'htmlall':'UTF-8'}</p>
	</div>
	<a class="label" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
		<i class="material-icons">chevron_left</i>{l s='Other payment methods' mod='payu'}
	</a>
{/if}
{/block}