var openpayu = openpayu || {};
openpayu.options = openpayu.options || {};

$(document).ready(function () {
    function moveRetryPayment() {
        var $retryPayment = $('#payuRetryPayment');
        var $infoOrder = $('.info-order').first();

        if ($retryPayment.length > 0 && $infoOrder.length > 0 && !$infoOrder.next().is($retryPayment)) {
            $retryPayment.insertAfter($infoOrder);
        }
    }

    var orderDetailObserver = new MutationObserver(moveRetryPayment);
    orderDetailObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    moveRetryPayment();

    $('body').on('click', '.payu-read-more', function () {
        $(this).hide();
        var elementToShow = $(this).data('more');
        $('body #' + elementToShow).show();
    });

    if (window.location.hash === '#repayment') {
        setTimeout(function () {
            $('html, body').animate({
                scrollTop: $(".repayment-container").offset().top
            }, 1000);
        }, 500)
    }

    $(document).on('click', '#HOOK_PAYMENT .payment_module a.payu', function (e) {
        if ($(this).attr('href') === '') {
            init_sf();
            $(this).parent().next('.payment_module_content').show();
            return false;
        } else {
            return doubleClickPrevent(this);
        }
    });
    $('.repayment-options .payMethod:not(.payMethodDisable)').on('click', function (e) {
        $('[name="transferGateway"]').val($(this).find('input').val());
    })
});

function doubleClickPrevent(object) {
    if ($(object).data('clicked')) {
        return false;
    }
    $(object).data('clicked', true);
    return true;
}

(function () {
    document.addEventListener("DOMContentLoaded", function () {
        function resetPaymentTab() {
            Array.from(document.querySelectorAll('.payment_module_content')).forEach(function (el) {
                el.classList.remove('payment_module_content--show');
            });
        }

        document.querySelectorAll('.payment_open')
            .forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();

                    resetPaymentTab();

                    if (e.currentTarget.hasAttribute('data-payment')) {
                        var paymentElement = element.getAttribute('data-payment');
                        var paymentContent = document.querySelector('[data-payment-open=' + paymentElement + ']');
                        paymentContent.classList.toggle('payment_module_content--show');
                    }
                });
            });

        function selectRetryPaymentOption(paymentOption) {
            var $radio = $(paymentOption);
            var $paymentOption = $radio.closest('.repayment-single');
            var $form = $paymentOption.closest('.repayment-options');
            var $additionalInformation = $paymentOption.parent().next('.additional-information');
            var payMethod = $additionalInformation.find('.payment-name').first().attr('data-pm');

            $form.find('[name="payMethod"]').val(payMethod || '');
            $form.find('[name="payment_id"]').val($radio.val());
            $form.find('.additional-information').hide();
            $additionalInformation.show();
        }

        $(document).on('change', '.repayment-options input[name="payment-option"]', function () {
            selectRetryPaymentOption(this);
        });

        $('.repayment-options input[name="payment-option"]:checked').each(function () {
            selectRetryPaymentOption(this);
        });

        validateBeforeSubmitCardForm();
        validateBeforeSubmitGatewaysForm();
        validateBeforeSubmitGooglePay();
        validateBeforeSubmitApplePay();


        function activatePaymentButton() {
            var btnSubmit = document.querySelector('.pay-transfer-accept button');
            if (btnSubmit !== null) {
                btnSubmit.removeAttribute("disabled");
            }
        }

        function resetAllGatewaysActive() {
            Array.from(document.querySelectorAll('.pay-methods__item')).forEach(function (el) {
                el.classList.remove('payMethodActive');
            });
            var $currentGateway = $('input[name=transferGateway]');
            $currentGateway && $currentGateway.val('');
        }

        function validateBeforeSubmitGatewaysForm() {
            var paymentTransferSubmit = document.querySelector('#paymentTransfer .btn');
            if (paymentTransferSubmit !== null) {
                paymentTransferSubmit.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    payuGatewaysValidate();
                    return false;
                });
            }
        }

        function validateBeforeSubmitCardForm() {
            if ($('.repayment-options').length > 0 && $('.repayment-options').hasClass('has-sf') && $('[name="payMethod"]').val() == 'card' || $('.repayment-options').length == 0) {
                var paymentCardSubmit = document.querySelector('#payment-confirmation .btn, .repayment-options input[type="submit"], #secure-form-pay');

                if (paymentCardSubmit !== null) {
                    paymentCardSubmit.addEventListener('click', function (e) {
                        if ($('#card-form-container').is(':visible')) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();

                            payuCardValidate();
                            return false;
                        }
                    });
                }
            }
        }

        function validateBeforeSubmitGooglePay() {
            var paymentGooglePaySubmit = document.querySelector('#payment-confirmation .btn, .repayment-options input[type="submit"], #google-pay-submit');

            if (paymentGooglePaySubmit !== null) {
                paymentGooglePaySubmit.addEventListener('click', function (e) {
                    var $retryForm = $(paymentGooglePaySubmit).closest('.repayment-options');
                    if ($retryForm.length > 0 && $retryForm.children('[name="payMethod"]').val() !== 'ap') {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    payuGooglePayValidate();
                    return false;
                });
            }
        }

        function validateBeforeSubmitApplePay() {
            var paymentApplePaySubmit = document.querySelector('#payment-confirmation .btn, .repayment-options input[type="submit"], #apple-pay-submit');

            if (paymentApplePaySubmit !== null) {
                paymentApplePaySubmit.addEventListener('click', function (e) {
                    var $retryForm = $(paymentApplePaySubmit).closest('.repayment-options');
                    if ($retryForm.length > 0 && $retryForm.children('[name="payMethod"]').val() !== 'jp') {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    payuApplePayValidate();
                    return false;
                });
            }
        }

        function payuGatewaysValidate() {
            var validateResponse = document.getElementById('transfer-response-box');
            var btn = document.querySelector('.pay-transfer-accept button');
            var form = document.querySelector('#paymentTransfer');

            var $currentGateway = $('input[name=transferGateway]');

            if ($currentGateway.val() === '') {
                validateResponse.style.display = 'block';
                btn.setAttribute('disabled', '');
            } else {
                if ($('.repayment-options').length == 0) {
                    form.submit()
                } else {
                    $('#paymentTransfer').closest('form').submit();
                }
            }
        }

        $(document).on('click', 'input[name=transfer_gateway_id]', function () {
            resetAllGatewaysActive();

            var gatewayValue = this.value;
            var item = document.querySelector('#payMethodContainer-' + gatewayValue);
            item.classList.add('payMethodActive');
            var $currentGateway = $('input[name=transferGateway]');
            if (gatewayValue !== null && $currentGateway) {
                $currentGateway.val(gatewayValue);
            }

            var transferResponseBox = document.getElementById('transfer-response-box')

            if (transferResponseBox !== null) {
                transferResponseBox.style.display = 'none';
            }

            activatePaymentButton();
        });

        init_sf();
        $('body').on('click', '.history_detail a', function () {
            setTimeout(function () {
                init_sf();
                validateBeforeSubmitCardForm();
                validateBeforeSubmitGatewaysForm();
                validateBeforeSubmitGooglePay();
                validateBeforeSubmitApplePay();
            }, 4000)
        });

        function payuCardValidate() {

            hideMessageBoxSecureForm();
            window.cardTokenInput.value = '';
            window.secureFormNumber.update({disabled: true});
            window.secureFormDate.update({disabled: true});
            window.secureFormCvv.update({disabled: true});
            $('.payment_module').css('pointer-events', 'none');
            try {
                window.payu.tokenize().then(function (result) {

                    if (result.status === 'SUCCESS') {
                        window.secureFormNumber.remove();
                        window.secureFormDate.remove();
                        window.secureFormCvv.remove();
                        window.cardTokenInput.value = result.body.token;
                        document.getElementsByName('payuBrowser[screenWidth]')[0].value = screen.width;
                        document.getElementsByName('payuBrowser[javaEnabled]')[0].value = navigator.javaEnabled();
                        document.getElementsByName('payuBrowser[timezoneOffset]')[0].value = new Date().getTimezoneOffset();
                        document.getElementsByName('payuBrowser[screenHeight]')[0].value = screen.height;
                        document.getElementsByName('payuBrowser[userAgent]')[0].value = navigator.userAgent;
                        document.getElementsByName('payuBrowser[colorDepth]')[0].value = screen.colorDepth;
                        document.getElementsByName('payuBrowser[language]')[0].value = navigator.language;
                        document.getElementById('waiting-box').style.display = '';
                        document.getElementById('card-form-container').style.display = 'none';
                        if ($('.repayment-options').length > 0) {
                            $('.repayment-options').submit();
                        } else {
                            document.getElementById('payu-card-form').submit();
                        }

                    } else {
                        $('.payment_module').css('pointer-events', 'unset');
                        var errorMessage = errorTitle;
                        result.error.messages.forEach(function (error) {
                            errorMessage += '<strong>' + error.message + '<strong><br>';
                        });

                        showMessageBoxSecureForm(errorMessage);

                        window.secureFormNumber.update({disabled: false});
                        window.secureFormDate.update({disabled: false});
                        window.secureFormCvv.update({disabled: false});
                    }
                });
            } catch (e) {
                showMessageBoxSecureForm(e.message);
            }
        }

        function payuGooglePayValidate() {
            hideMessageBoxGooglePay();
            if (!window.google?.payments?.api?.PaymentsClient) {
                showMessageBoxGooglePay(googlePayErrorMessage);
                return false;
            }
            var googleToken = document.getElementById('payu-google-token');

            if (googleToken.value === '') {
                const paymentsClient =
                    new google.payments.api.PaymentsClient({environment: env});

                const isReadyToPayRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [
                        {
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                                allowedCardNetworks: ['MASTERCARD', 'VISA']
                            }
                        }
                    ]
                }

                const paymentDataRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    merchantInfo: {
                        merchantName,
                        merchantId,
                    },
                    allowedPaymentMethods: [
                        {
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                                allowedCardNetworks: ['MASTERCARD', 'VISA'],
                                billingAddressRequired: false
                            },
                            tokenizationSpecification: {
                                type: 'PAYMENT_GATEWAY',
                                parameters: {
                                    gateway: 'payu',
                                    gatewayMerchantId: posId
                                }
                            }
                        }
                    ],
                    transactionInfo: {
                        totalPriceStatus: 'FINAL',
                        countryCode: 'PL',
                        totalPrice,
                        currencyCode: currency
                    }
                }

                paymentsClient.isReadyToPay(isReadyToPayRequest)
                    .then(function (response) {
                        if (response.result) {
                            paymentsClient.loadPaymentData(paymentDataRequest).then(function (paymentData) {
                                paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                                googleToken.value = btoa(paymentToken);
                                document.getElementById('payu-google-pay-form').submit();
                            }).catch(function (err) {
                                console.error(err);
                            });
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        showMessageBoxGooglePay(googlePayErrorMessage);
                    });

                return false;
            } else {
                return true;
            }
        }

        function payuApplePayValidate() {
            var APPLE_PAY_API_MIN_VERSION = 1;
            var APPLE_PAY_API_MAX_VERSION = 14;

            hideMessageBoxApplePay();

            if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
                showMessageBoxApplePay(applePayErrorMessage);
                return false;
            }

            var applePayToken = document.getElementById('payu-apple-token');
            if (applePayToken.value !== '') {
                return true;
            }

            var applePayApiVersion = APPLE_PAY_API_MIN_VERSION;
            for (var i = APPLE_PAY_API_MAX_VERSION; i = APPLE_PAY_API_MIN_VERSION; i--) {
                if (ApplePaySession.supportsVersion(i)) {
                    applePayApiVersion = i;
                    break;
                }
            }

            var applePayPaymentRequest = {
                countryCode: 'PL',
                currencyCode: currency,
                total: {
                    type: 'final',
                    label: displayName,
                    amount: totalPrice
                },
                supportedNetworks: ['visa', 'masterCard'],
                merchantCapabilities: ['supports3DS']
            };

            var applePaySession = new ApplePaySession(
                applePayApiVersion,
                applePayPaymentRequest
            );

            var abortSession = function () {
                showMessageBoxApplePay(applePayErrorMessage);
                applePaySession.abort();
            };

            applePaySession.onvalidatemerchant = function () {
                var url = new URL(applePaySessionUrl);
                url.searchParams.set('ajax', '1');

                fetch(url.toString(), {
                    headers: {'Content-Type': 'application/json'},
                })
                    .then(function (sessionResponse) {
                        if (!sessionResponse.ok) {
                            throw new Error('Apple Pay merchant validation failed');
                        }
                        return sessionResponse.json();
                    })
                    .then(function (session) {
                        applePaySession.completeMerchantValidation(session);
                    })
                    .catch(function (error) {
                        console.error('PayU Apple Pay session validation failed:', error);
                        abortSession();
                    });
            };

            applePaySession.onpaymentauthorized = function (event) {
                applePaySession.completePayment(ApplePaySession.STATUS_SUCCESS);
                applePayToken.value = btoa(
                    JSON.stringify(event.payment.token.paymentData)
                );
                if ($('.repayment-options').length > 0) {
                    $('.repayment-options').submit();
                } else {
                    document.getElementById('payu-apple-pay-form').submit();
                }
            };

            applePaySession.begin();
            return false;
        }
    });
})();

function init_sf() {
    if (payuSFEnabled === true && typeof PayU !== 'undefined') {

        var secureFormOptions = {
            elementFormNumber: '#payu-card-number',
            elementFormDate: '#payu-card-date',
            elementFormCvv: '#payu-card-cvv',
            element: '#secure-form',
            profile: 'widthGt300',
            profiles: {
                widthLt290: {
                    cardIcon: false,
                    style: {
                        basic: {
                            fontSize: '14px',
                        }
                    },
                },
                widthLt340: {
                    cardIcon: true,
                    style: {
                        basic: {
                            fontSize: '14px',
                        }
                    },
                },
                widthGt340: {
                    cardIcon: true,
                    style: {
                        basic: {
                            fontSize: '18px',
                        }
                    },
                }
            },
            config: {
                cardIcon: true,
                placeholder: {
                    number: '',
                    cvv: ''
                },
                style: {
                    basic: {
                        fontSize: '18px',
                    }
                },
                lang: payuLangId
            }
        };

        secureFormOptions.profile = calculateProfile();
        secureFormOptions.config = Object.assign({}, secureFormOptions.config, secureFormOptions.profiles[secureFormOptions.profile]);

        window.payu = PayU(payuPosId);

        var secureForms = payu.secureForms();
        window.secureFormNumber = secureForms.add('number', secureFormOptions.config);
        window.secureFormNumber.render(secureFormOptions.elementFormNumber);
        window.secureFormDate = secureForms.add('date', secureFormOptions.config);
        window.secureFormDate.render(secureFormOptions.elementFormDate);
        window.secureFormCvv = secureForms.add('cvv', secureFormOptions.config);
        window.secureFormCvv.render(secureFormOptions.elementFormCvv);
        window.addEventListener('resize', secureFormResize);
        window.cardTokenInput = document.getElementById('card-token');

    }
}

function calculateProfile() {
    if (window.innerWidth <= 290) {
        return 'widthLt290';
    } else if (window.innerWidth <= 340) {
        return 'widthLt340';
    }

    return 'widthGt340';
}

function secureFormResize() {
    var newProfile = calculateProfile();

    if (newProfile !== secureFormOptions.profile) {
        secureFormOptions.profile = newProfile;
        window.secureFormNumber.update(secureFormOptions.profiles[secureFormOptions.profile]);
        window.secureFormDate.update(secureFormOptions.profiles[secureFormOptions.profile]);
        window.secureFormCvv.update(secureFormOptions.profiles[secureFormOptions.profile]);
    }
}

function showMessageBox(elementId, message) {
    var responseBox = document.getElementById(elementId);
    responseBox.innerHTML = message;
    responseBox.style.display = '';
}

function hideMessageBox(elementId) {
    var responseBox = document.getElementById(elementId);
    responseBox.innerHTML = '';
    responseBox.style.display = 'none';
}

function showMessageBoxSecureForm(message) {
    showMessageBox('response-box-secure-form', message);
}

function hideMessageBoxSecureForm() {
    hideMessageBox('response-box-secure-form');
}

function showMessageBoxGooglePay(message) {
    showMessageBox('response-box-google-pay', message);
}

function hideMessageBoxGooglePay() {
    hideMessageBox('response-box-google-pay');
}

function showMessageBoxApplePay(message) {
    showMessageBox('response-box-apple-pay', message);
}

function hideMessageBoxApplePay() {
    hideMessageBox('response-box-apple-pay');
}
