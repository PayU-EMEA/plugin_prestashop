{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
{extends file=$layout}

{block name='content'}
    <section id="main">
        <script type="text/javascript" src="{$jsSdk}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/es6-promise/4.1.1/es6-promise.auto.min.js"></script>

        <div class="clearfix">
            <h2 id="payuAmountInfo">{$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}</h2>
            <img src="{$image}" id="payuLogo">
        </div>

        {if $payuErrors|@count}
            <div class="alert alert-warning">
                {foreach $payuErrors as $error}
                    {$error}<br>
                {/foreach}
            </div>
        {/if}
        <section id="content" class="page-content page-cms">
            <form action="{$payuPayAction|escape:'html'}" method="post" id="payu-card-form">
                <input type="hidden" name="payuPay" value="1" />
                <input type="hidden" name="payMethod" value="card" />
                <input type="hidden" name="cardToken" value="" id="card-token" />
                <div id="card-form-container">
                    {if isset($payMethods.error)}
                        <h4 class="error">{l s='Error has occurred' mod='payu'}: {$payMethods.error}</h4>
                    {else}
                        <div id="payMethods" style="padding-bottom: 5px">
                            <div id="response-box" class="alert alert-warning" style="display: none; margin-bottom: 10px"></div>
                            <div class="payu-card-form-container">
                                <div class="payu-card-legend-container">
                                    <div class="payu-card-legend-number">{l s='Card number' mod='payu'}:</div>
                                    <div class="payu-card-legend-valid">{l s='Valid thru' mod='payu'}:</div>
                                    <div>{l s='CVV' mod='payu'}:</div>
                                </div>
                                <div id="card-form"></div>
                            </div>
                        </div>
                        {include file='module:payu/views/templates/front/conditions17.tpl'}
                    {/if}

                    <p class="cart_navigation clearfix" id="cart_navigation">
                        {if !$retryPayment}
                            <a class="label" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                                <i class="material-icons">chevron_left</i>{l s='Other payment methods' mod='payu'}
                            </a>
                        {/if}
                        {if !isset($payMethods.error)}
                            <button class="btn btn-primary float-xs-right continue" type="submit" id="secure-form-pay">
                                <span>{if !$retryPayment}{l s='I confirm my order' mod='payu'}{else}{l s='Pay' mod='payu'}{/if}<i class="material-icons">chevron_right</i></span>
                            </button>
                        {/if}
                    </p>
                </div>
                <div id="waiting-box" style="display: none">{l s='Please wait' mod='payu'}...</div>
            </form>
        </section>

        <script>
            (function () {
                var secureFormOptions = {
                    style: {
                        basic: {
                            fontSize: '18px',
                        }
                    },
                    lang: '{$lang}'
                };

                var payu = PayU({$posId});
                var secureForm = payu.secureForm('card', secureFormOptions);
                secureForm.render('#card-form');

                var payButton = document.getElementById('secure-form-pay');
                var responseBox = document.getElementById('response-box');
                var cardTokenInput = document.getElementById('card-token');

                payButton.addEventListener('click', function(event) {
                    event.preventDefault();

                    var isAcceptPayuConditions = document.getElementById('payuCondition').checked;

                    if (!isAcceptPayuConditions) {
                        showMessageBox('<strong>{l s='Please accept "Terms of single PayU payment transaction"' mod="payu"}</strong>');
                        return;
                    }

                    hideMessageBox();
                    cardTokenInput.value = '';
                    secureForm.update({ disabled: true });

                    try {
                        payu.tokenize().then(function(result) {
                            if (result.status === 'SUCCESS') {
                                secureForm.remove();
                                cardTokenInput.value = result.body.token;
                                document.getElementById('waiting-box').style.display = '';
                                document.getElementById('card-form-container').style.display = 'none';
                                document.getElementById('payu-card-form').submit();
                            } else {
                                var errorMessage = '{l s="There were errors saving the card" mod="payu"}:<br>';
                                result.error.messages.forEach(function(error) {
                                    errorMessage += '<strong>' + error.message + '<strong><br>';
                                });

                                showMessageBox(errorMessage);

                                secureForm.update({ disabled: false });
                            }
                        });
                    } catch(e) {
                        console.log(e);
                    }
                });

                function showMessageBox(message) {
                    responseBox.innerHTML = message;
                    responseBox.style.display = '';
                }

                function hideMessageBox() {
                    responseBox.innerHTML = '';
                    responseBox.style.display = 'none';
                }

            })();
        </script>
    </section>
{/block}