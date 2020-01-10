<div class="payu-method-description">
    <img class="payu-marker-class payu-later-logo" src="{$payu_later_logo_img}"/>
    <p>
        {l s='Pay by card or online transfer up to 30 days after purchase, no additional fees' mod='payu'}
        <span id='payu-later-mini-cart'>
        <img src="{$payu_question_mark_img}"/>
    </span>
        <script type='text/javascript' class="payu-script-tag">
            document.addEventListener("DOMContentLoaded", function (event) {
                var options = {
                    amount: {$total_price},
                    customElement: true,
                    lang: 'pl'
                }
                DelayedPayment.miniDelayedPayment('#payu-later-mini-cart', options);
            });
        </script>
    </p>
    <p>
        {l s='Order will be done after positive decision' mod='payu'}
    </p>
</div>
<script type="text/javascript">
    (function () {
        window.payuPaymentLoaded = true;
    })();
</script>
