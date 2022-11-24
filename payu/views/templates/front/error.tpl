{*
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
{capture name=path}{l s='Pay with PayU' mod='payu'}{/capture}

<div class="clearfix">
	<h2>
		{$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}
	</h2>
</div>


<div class="alert alert-warning">
	{$payuError}
</div>
