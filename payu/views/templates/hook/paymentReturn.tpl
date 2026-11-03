{*
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}

<div style="text-align: center">
    <div><img src="{$payuLogo}" alt="PayU"></div>
    <h2>{l s='Thanks for choosing PayU payment.' mod='payu'}</h2>
    {if $show_status === '1'}
        <script>
            window.payu_get_status_url = {$url|@json_encode nofilter};
        </script>

        <div id="status-waiting">
            <h2>
            {l s='Wait for the payment result' mod='payu'}
                <div id="payu-preloader">
                    <output aria-live="polite" aria-busy="true">
                        <img src="{$spinner_url}" alt="..." role="img"/>
                    </output>
                </div>
            </h2>
        </div>
        <h2 id="status-result-completed" style="display: none">{l s='Your payment was successful.' mod='payu'}</h2>
        <h2 id="status-result-pending" style="display: none">{l s='Your payment is being processed.' mod='payu'}</h2>
        <h2 id="status-result-canceled" style="display: none">{l s='Your payment failed.' mod='payu'}</h2>
        <h2 id="status-result-error" style="display: none">{l s='Unknown Error' mod='payu'}</h2>
    {/if}
</div>

