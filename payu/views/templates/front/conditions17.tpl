<div class="payuConditions">
    <div class="checkbox">
        <input type="checkbox" value="1" {if $payuConditions}checked="checked"{/if} name="payuConditions" id="payuCondition">
        <label for="payuCondition">
            {l s='I accept' mod='payu'} <a target="_blank" href="{$conditionUrl}">{l s='Terms of single PayU payment transaction' mod='payu'}</a>
        </label>
    </div>
    {l s="Payment is processed by PayU SA; The recipient's data, the payment title and the amount are provided to PayU SA by the recipient;" mod='payu'} <span class="payu-read-more" data-more="more1">{l s="read more" mod='payu'}</span><span class="payu-more-hidden" id="more1"> {l s="The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees." mod='payu'}</span><br />
    {l s='The controller of your personal data is PayU S.A. with its registered office in Poznan (60-166), at Grunwaldzka Street 182 ("PayU").' mod='payu'} <span class="payu-read-more" data-more="more2">{l s="read more" mod='payu'}</span>
    <span class="payu-more-hidden" id="more2"> {l s='Your personal data will be processed for purposes of processing  payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.' mod='payu'}
    <br />
    {l s='The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.' mod='payu'}
    <br />
    {l s='You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click' mod='payu'} <a href="{l s='https://static.payu.com/sites/terms/files/payu_privacy_policy_en_en.pdf' mod='payu'}" target="_blank">{l s='Payu Privacy Policy' mod='payu'}</a>.
    </span>
</div>
