var openpayu = openpayu || {};
openpayu.options = openpayu.options || {};

$(document).ready(function () {
    $('#payuRetryPayment17').insertBefore($('#order-history'));

    $('body').on('click', '.payu-read-more', function () {
        $(this).hide();
        var elementToShow = $(this).data('more');
        $('body #' + elementToShow).show();
    });
    if ($('.repayment-single').length > 0) {
        $('.pay-methods__item:not(.payMethodDisable)').on('click', function () {
            $('[name="payMethod"]').val($(this).find('input').val());
        });
    }
    if (window.location.hash == '#repayment') {
        setTimeout(function () {
            $('html, body').animate({
                scrollTop: $(".repayment-container").offset().top
            }, 1000);
        }, 500)
    }
});

(function () {
    document.addEventListener("DOMContentLoaded", function () {

        var transferResponseBox = document.getElementById('transfer-response-box');
        var transferGateways = document.querySelectorAll('input[name=transfer_gateway_id]');
        var currentGateway = document.querySelector('input[name=transfer_gateway1]');


        document.querySelectorAll('div.payment-option, .repayment-single').forEach(function (element) {
            element.addEventListener('click', function (ev) {
                    if ($(element).hasClass('repayment-single')) {
                        $(element).closest('.repayment-options').find('.additional-information').hide();
                        $(element).parent('div').next('.additional-information').show();
                        $('[name="payMethod"]').val($(element).parent('div').next('.additional-information').find('.payment-name').attr('data-pm'));
                    }
                    ev.stopPropagation();

		            if(currentGateway !== null){
			            currentGateway.value = '';
		            }

                    var id = ev.target.id.slice('15');
                    id = id.replace('-container', '');
                    $('[name="payment_id"]').val(id);
                    Array.from(document.querySelectorAll('.pay-methods__item')).forEach(function (el) {
                        el.classList.remove('payMethodActive');
                    });

                    if (id) {
                        var paymentContent = document.querySelector('#payment-option-' + id + '-additional-information');
                        var paymentChildren = null;

						if(paymentContent !== null && paymentContent.children.length > 0) {
							paymentChildren = paymentContent.children;
						}

                        if (paymentChildren) {
                            Array.from(paymentChildren).forEach(function (el) {
                                if (el.className === 'pay-card-init') {
                                    validateBeforeSubmitCardForm();
                                }
                            });
                        }
                    }
                }, true
            );
        });

        function validateBeforeSubmitCardForm() {
            document.querySelector('#payment-confirmation .btn, .repayment-options input[type="submit"], #secure-form-pay')
                .addEventListener('click', function (e) {
                    if($('.pay-card-init').is(':visible')) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        payuCardValidate();
                    }
                    return false;
                });
        }


        function activatePaymentButton() {
            var paymentSubmit = document.querySelector('.pay-transfer-accept button');
            if (paymentSubmit !== null) {
                paymentSubmit.removeAttribute("disabled");
            }
        }

        function resetAllGatewaysActive() {
            Array.from(document.querySelectorAll('.pay-methods__item')).forEach(function (el) {
                el.classList.remove('payMethodActive');
            });

	        if (currentGateway !== null) {
		        currentGateway.value = '';
	        }
        }


        if (transferGateways.length > 0) {
            transferGateways.forEach(function (gateway) {
                gateway.addEventListener('click', function (e) {

                        resetAllGatewaysActive();

                        var gatewayValue = this.value;
                        var gatewayItem = document.querySelector('#payMethodContainer-' + gatewayValue);
                        gatewayItem.classList.add('payMethodActive');

	                    if (gatewayValue !== null && currentGateway !== null) {
                            currentGateway.value = gatewayValue;
                        }

                        if (transferResponseBox !== null) {
                            transferResponseBox.style.display = 'none';
                        }

                        activatePaymentButton();

                    }, true
                );
            });
        }

        // Polyfill from https://developer.mozilla.org/pl/docs/Web/JavaScript/Referencje/Obiekty/Object/assign
        "function" != typeof Object.assign && (Object.assign = function (n, t) {
            "use strict";
            if (null == n) throw new TypeError("Cannot convert undefined or null to object");
            for (var r = Object(n), e = 1; e < arguments.length; e++) {
                var o = arguments[e];
                if (null != o) for (var c in o) Object.prototype.hasOwnProperty.call(o, c) && (r[c] = o[c])
            }
            return r
        });

        // Polyfill Array.from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/from#polyfill
        Array.from || (Array.from = function () {
            var r;
            try {
                r = Symbol.iterator ? Symbol.iterator : "Symbol(Symbol.iterator)"
            } catch (t) {
                r = "Symbol(Symbol.iterator)"
            }
            var t = Object.prototype.toString, n = function (r) {
                return "function" == typeof r || "[object Function]" === t.call(r)
            }, o = Math.pow(2, 53) - 1, e = function (r) {
                var t = function (r) {
                    var t = Number(r);
                    return isNaN(t) ? 0 : 0 !== t && isFinite(t) ? (t > 0 ? 1 : -1) * Math.floor(Math.abs(t)) : t
                }(r);
                return Math.min(Math.max(t, 0), o)
            };
            return function (t) {
                var o = Object(t), a = n(o[r]);
                if (null == t && !a) throw new TypeError("Array.from requires an array-like object or iterator - not null or undefined");
                var i, u = arguments.length > 1 ? arguments[1] : void 0;
                if (void 0 !== u) {
                    if (!n(u)) throw new TypeError("Array.from: when provided, the second argument must be a function");
                    arguments.length > 2 && (i = arguments[2])
                }
                var f = e(o.length);
                return function (r, t, n, o, e, a) {
                    for (var i = 0; i < n || e;) {
                        var u = o(i), f = e ? u.value : u;
                        if (e && u.done) return t;
                        t[i] = a ? void 0 === r ? a(f, i) : a.call(r, f, i) : f, i += 1
                    }
                    if (e) throw new TypeError("Array.from: provided arrayLike or iterator has length more then 2 ** 52 - 1");
                    return t.length = n, t
                }(i, n(this) ? Object(new this(f)) : new Array(f), f, function (t, n) {
                    var o = t && n[r]();
                    return function (r) {
                        return t ? o.next() : n[r]
                    }
                }(a, o), a, u)
            }
        }());


		if(payuSFEnabled === true && typeof PayU !== 'undefined') {
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

		    var payu = PayU(payuPosId, {dev: true});

		    var secureForms = payu.secureForms();
		    var secureFormNumber = secureForms.add('number', secureFormOptions.config);
		    secureFormNumber.render(secureFormOptions.elementFormNumber);
		    var secureFormDate = secureForms.add('date', secureFormOptions.config);
		    secureFormDate.render(secureFormOptions.elementFormDate);
		    var secureFormCvv = secureForms.add('cvv', secureFormOptions.config);
		    secureFormCvv.render(secureFormOptions.elementFormCvv);
		    window.addEventListener('resize', secureFormResize);

		    var responseBox = document.getElementById('response-box');
		    var cardTokenInput = document.getElementById('card-token');
		}


        function payuCardValidate() {

            hideMessageBox();
            cardTokenInput.value = '';
            secureFormNumber.update({disabled: true});
            secureFormDate.update({disabled: true});
            secureFormCvv.update({disabled: true});

            try {
                payu.tokenize().then(function (result) {
                $('#payment-confirmation .btn')
                    .attr('disabled', 'disabled')
                    .addClass('disabled disabled-by-payu');
                    if (result.status === 'SUCCESS') {
                        secureFormNumber.remove();
                        secureFormDate.remove();
                        secureFormCvv.remove();
                        cardTokenInput.value = result.body.token;
                        document.getElementById('waiting-box').style.display = '';
                        document.getElementById('card-form-container').style.display = 'none';
                        if ($('.repayment-options').length > 0) {
                            $('.repayment-options').submit();
                        } else {
                            document.getElementById('payu-card-form').submit();
                        }

                    } else {
                        var errorMessage = errorTitle;
                        if($('#payment-confirmation .btn').hasClass('disabled-by-payu')) {
                            $('#payment-confirmation .btn')
                                .removeAttr('disabled', 'disabled')
                                .removeClass('disabled');
                        }
                        result.error.messages.forEach(function (error) {
                            errorMessage += '<strong>' + error.message + '<strong><br>';
                        });

                        showMessageBox(errorMessage);

                        secureFormNumber.update({disabled: false});
                        secureFormDate.update({disabled: false});
                        secureFormCvv.update({disabled: false});
                    }
                });
            } catch (e) {
                showMessageBox(e.message);
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
                secureFormNumber.update(secureFormOptions.profiles[secureFormOptions.profile]);
                secureFormDate.update(secureFormOptions.profiles[secureFormOptions.profile]);
                secureFormCvv.update(secureFormOptions.profiles[secureFormOptions.profile]);
            }
        }

        function showMessageBox(message) {
            responseBox.innerHTML = message;
            responseBox.style.display = '';
        }

        function hideMessageBox() {
            responseBox.innerHTML = '';
            responseBox.style.display = 'none';
        }

    })
})();

