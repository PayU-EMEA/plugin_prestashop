{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $show_refund}
	{capture assign=refund_fieldset}
		<fieldset>
			<legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.gif" alt="" />{l s='Make a full or partial refund' mod='payu'}</legend>
			<form action="" method="post" onsubmit="return confirm('{l s='Do you really want to submit the refund request?' mod='payu'}');">
				<select name="payu_refund_type" id="payu_refund_type">
					<option value="full"{if $payu_refund_type eq "full"} selected="selected"{/if}>{l s='Full refund' mod='payu'}</option>
					<option value="partial"{if $payu_refund_type eq "partial"} selected="selected"{/if}>{l s='Partial refund' mod='payu'}</option>
				</select>
				<label style="float: none">
					{l s='amount' mod='payu'}
					<input type="text" id="payu_refund_amount" name="payu_refund_amount" value="{$payu_refund_amount|escape:'htmlall':'UTF-8'}"/>
				</label>
				<input type="submit" name="submitPayuRefund" class="button" value="{l s='Perform refund' mod='payu'}"/>
			</form>
			{if $payu_refund_errors|count}
				<br/>
				{foreach from = $payu_refund_errors item = error}
					<p class="error">{$error|escape:'htmlall':'UTF-8'}</p>
				{/foreach}
			{/if}
		</fieldset>
		<br/>
		<script>
			{literal}
			$(document).ready(function() {
				var refund_type_select = $('#payu_refund_type');
				var set_type = function(type) {
					if ('full' == type) {
						$('#payu_refund_amount').attr('readonly', true).val('{/literal}{$payu_refund_full_amount|escape:'htmlall':'UTF-8'}{literal}');
					} else {
						$('#payu_refund_amount').attr('readonly', false);
					}
				};
				set_type(refund_type_select.val());
				refund_type_select.on('change', function(){
					set_type(refund_type_select.val());
				});
			});
			{/literal}
		</script>
	{/capture}
	<script>
		$(document).ready(function() {
			$("{$refund_fieldset|escape:'javascript':'UTF-8'}").insertBefore($('select[name=id_order_state]').parent().parent().find('fieldset').first());
		});
	</script>
{/if}
