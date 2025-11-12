{*
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
<script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js"></script>
<script type="text/javascript" src="{$jsSdk}" defer></script>
<span class="payment-name" data-pm="card"></span>
{if isset($payuNotifications.card)}
    <div id="transfer-response-box" class="alert alert-warning" style="margin-bottom: 10px;">
        {foreach $payuNotifications.card as $error}
            {$error}
            <br>
        {/foreach}
    </div>
{/if}
<section id="main" class="pay-card-init">
    {if !$retryPayment }
        <form action="{$payuPayAction|escape:'html'}" method="post" id="payu-card-form">
            <input type="hidden" name="payment_id" value="">
            <input type="hidden" name="payMethod" value="card"/>
            <input type="hidden" name="cardToken" value="" id="card-token"/>
            <input type="hidden" name="payuBrowser[screenWidth]" value=""/>
            <input type="hidden" name="payuBrowser[javaEnabled]" value=""/>
            <input type="hidden" name="payuBrowser[timezoneOffset]" value=""/>
            <input type="hidden" name="payuBrowser[screenHeight]" value=""/>
            <input type="hidden" name="payuBrowser[userAgent]" value=""/>
            <input type="hidden" name="payuBrowser[colorDepth]" value=""/>
            <input type="hidden" name="payuBrowser[language]" value=""/>
            <div id="card-form-container">
                {if isset($payMethods.error)}
                    <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
                {else}
                    <div id="payMethods" style="padding-bottom: 5px">
                        <div id="response-box" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
                        {include file='module:payu/views/templates/front/payuCardForm.tpl'}
                    </div>
                    {include file='module:payu/views/templates/front/conditions17.tpl'}
                {/if}
            </div>
            <div id="waiting-box" style="display: none; margin-top: 24px; margin-bottom: 24px;">
                <div class="alert alert-info">
                    {l s='Please wait' mod='payu'}...
                </div>
            </div>
        </form>
    {else}
        <div action="{$payuPayAction|escape:'html'}" method="post" id="payu-card-form">
            <input type="hidden" name="payment_id" value="">
            <input type="hidden" name="payMethod" value="card"/>
            <input type="hidden" name="cardToken" value="" id="card-token"/>
            <input type="hidden" name="payuBrowser[screenWidth]" value=""/>
            <input type="hidden" name="payuBrowser[javaEnabled]" value=""/>
            <input type="hidden" name="payuBrowser[timezoneOffset]" value=""/>
            <input type="hidden" name="payuBrowser[screenHeight]" value=""/>
            <input type="hidden" name="payuBrowser[userAgent]" value=""/>
            <input type="hidden" name="payuBrowser[colorDepth]" value=""/>
            <input type="hidden" name="payuBrowser[language]" value=""/>
            <div id="card-form-container">
                {if isset($payMethods.error)}
                    <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
                {else}
                    <div id="payMethods" style="padding-bottom: 5px">
                        <div id="response-box" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
                        {include file='module:payu/views/templates/front/payuCardForm.tpl'}
                    </div>
                    {include file='module:payu/views/templates/front/conditions17.tpl'}
                {/if}
            </div>
            <div id="waiting-box" style="display: none; margin-top: 24px; margin-bottom: 24px;">
                <div class="alert alert-info">
                    {l s='Please wait' mod='payu'}...
                </div>
            </div>
        </div>
    {/if}
</section>
{include file='module:payu/views/templates/front/secureFormJs.tpl'}
