{*
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
<script src="https://pay.google.com/gp/p/js/pay.js"></script>
<script type="text/javascript">
    var env = "{$env}";
    var merchantName = "{$merchantName}";
    var merchantId = "{$merchantId}";
    var posId = "{$posId}";
    var totalPrice = "{$totalPrice}";
    var currency = "{$currency}";
    var googlePayErrorMessage = "{l s='This payment method is not available.' mod='payu'}";
</script>
<span class="payment-name" data-pm="ap"></span>
{if !$retryPayment }
    <form action="{$payuPayAction|escape:'html'}" method="post" id="payu-google-pay-form">
        <input type="hidden" name="payment_id" value="">
        <input type="hidden" name="payuGoogleToken" id="payu-google-token" value="">
        <input type="hidden" name="payMethod" value="ap">
        {if isset($payMethods.error)}
            <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
        {else}
            <div id="response-box-google-pay" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
        {/if}
        {include file="$modulePath/views/templates/front/conditions17.tpl"}
        <p class="clearfix" id="cart_navigation" style="margin:24px 0">
            <button class="button btn btn-default button-medium center-block"
                    style="margin: 0 auto;display: table; float:none" type="submit" id="google-pay-submit">
                <span>
                    {l s='I confirm my order' mod='payu'}
                    <i class="icon-chevron-right right"></i>
                </span>
            </button>
        </p>
    </form>
{else}
    <div action="{$payuPayAction|escape:'html'}" method="post" id="payu-google-pay-form">
        <input type="hidden" name="payment_id" value="">
        <input type="hidden" name="payuGoogleToken" id="payu-google-token" value="">
        <input type="hidden" name="payMethod" value="ap">
        <div id="response-box-google-pay" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
        {include file="$modulePath/views/templates/front/conditions17.tpl"}
    </div>
{/if}
