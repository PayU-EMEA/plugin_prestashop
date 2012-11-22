{*
*	ver. 0.1.5
*	PayU Payment Modules
*	
*	@copyright  Copyright 2012 by PayU
*	@license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
*	http://www.payu.com
*	http://twitter.com/openpayu
*}
<form action="{$summaryUrl}" method="get" id="payu_form"> 
	<input type="hidden" name="sessionId" value="{$sessionId}">
	<input type="hidden" name="oauth_token" value="{$accessToken}">
</form>
<script>
	getElementById('payu_form').submit();
</script>