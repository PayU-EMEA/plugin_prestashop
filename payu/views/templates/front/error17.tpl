{*
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{extends file=$layout}

{block name='content'}

	<div class="clearfix">
		<h2 id="payuAmountInfo">{$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}</h2>
		<img src="{$image}" id="payuLogo">
	</div>

    {if $payuError}
		<div class="alert alert-warning">
            {$payuError}
		</div>
    {/if}

	<p class="cart_navigation clearfix" id="cart_navigation">
		<a class="btn btn-primary float-xs-right continue" href="{$buttonAction}">
			<span>{l s='Retry pay with PayU' mod='payu'}</span>
		</a>
	</p>

{/block}