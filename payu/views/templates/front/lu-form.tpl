{*
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{capture name=path}{l s='Payment' mod='payu'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<img src="{$base_dir|escape:'htmlall':'UTF-8'}modules/payu/img/loading.gif" alt="Loading" style="margin:auto;display: block;"/>
<br/>
Please wait...
<script>
	var form = {literal}${/literal}("{$luForm|escape:'javascript':'UTF-8'}");
	$(document.body).append(form);
	setTimeout(function() {
		form.trigger("submit");
	}, 300);
</script>
