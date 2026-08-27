<script src="https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js"
        crossorigin="anonymous">
</script>
<script type="text/javascript">
    var env = "{$env}";
    var posId = "{$posId}";
    var totalPrice = "{$totalPrice|escape:'javascript'}";
    var currency = "{$currency|escape:'javascript'}";
    var applePaySessionUrl = "{$applePaySessionUrl|escape:'javascript'}";
    var domainName = "{$domainName|escape:'javascript'}";
    var displayName = "{$displayName|escape:'javascript'}";
    var applePayErrorMessage = "{l s='This payment method is not available.' mod='payu'}";
</script>
<span class="payment-name" data-pm="jp"></span>
{if !$retryPayment }
    <form action="{$payuPayAction|escape:'html'}" method="post" id="payu-apple-pay-form">
        <input type="hidden" name="payment_id" value="">
        <input type="hidden" name="payuApplePayToken" id="payu-apple-token" value="">
        <input type="hidden" name="payMethod" value="jp">
        {if isset($payMethods.error)}
            <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
        {else}
            <div id="response-box-apple-pay" class="alert alert-warning"
                 style="display: none; margin-bottom: 10px"></div>
        {/if}
        {include file='module:payu/views/templates/front/conditions17.tpl'}
    </form>
{else}
    <div action="{$payuPayAction|escape:'html'}" method="post" id="payu-apple-pay-form">
        <input type="hidden" name="payment_id" value="">
        <input type="hidden" name="payuApplePayToken" id="payu-apple-token" value="">
        <input type="hidden" name="payMethod" value="jp">
        <div id="response-box-apple-pay" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
        {include file='module:payu/views/templates/front/conditions17.tpl'}
    </div>
{/if}
