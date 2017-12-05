{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2017 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{if $show_refund}
	{capture assign=refund_fieldset}
		<div class="well">
			<h3><img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.gif" alt="" /> {l s='Make a full or partial refund' mod='payu'}</h3>
			<div class="row">
			<form action="" method="post" onsubmit="return confirm('{l s='Do you really want to submit the refund request?' mod='payu'}');">
				<div class="col-lg-4">
				<select name="payu_refund_type" id="payu_refund_type">
					<option value="full"{if $payu_refund_type eq "full"} selected="selected"{/if}>{l s='Full refund' mod='payu'}</option>
					<option value="partial"{if $payu_refund_type eq "partial"} selected="selected"{/if}>{l s='Partial refund' mod='payu'}</option>
				</select>
				</div>
				<div class="col-lg-1" style="text-align: right">
					{l s='amount' mod='payu'}
				</div>
				<div class="col-lg-4">
					<input type="text" id="payu_refund_amount" name="payu_refund_amount" value="{$payu_refund_amount|escape:'htmlall':'UTF-8'}"/>
				</div>
				<div class="col-lg-2">
				<input type="submit" name="submitPayuRefund" class="btn btn-primary" value="{l s='Perform refund' mod='payu'}"/>
				</div>
			</form>
			</div>
			{if $payu_refund_errors|count}
				<br/>
				{foreach from = $payu_refund_errors item = error}
					<p class="error">{$error|escape:'htmlall':'UTF-8'}</p>
				{/foreach}
			{/if}
		</div>
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
			$("{$refund_fieldset|escape:'javascript':'UTF-8'}").insertAfter($('.panel-heading').first());
		});
	</script>
{/if}
