<img class="payu-later-logo" src="modules/payu/img/payu_later_logo.png" />
<p>
    {l s='Pay by card or online transfer within 30 days with no additional fees' mod='payu'}
    <span id='payu-later-mini-cart'>
        <img src="modules/payu/img/question_mark.png" />
    </span>
    <script type='text/javascript' class="payu-script-tag" >
        document.addEventListener("DOMContentLoaded", function(event) {
            openpayu.options.amount ={$total_price};
            openpayu.options.customElement = true;
            openpayu.options.lang = 'pl';
            OpenPayU.DelayedPayment.miniDelayedPayment('#payu-later-mini-cart');
        });
    </script>
</p>
<p>
    {l s='Order will be done after positive decision' mod='payu'}
</p>
