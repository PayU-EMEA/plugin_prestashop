var openpayu = openpayu || {};
openpayu.options = openpayu.options || {};

$(document).ready(function () {
	$('#payuRetryPayment').insertAfter($('.info-order').first());

	$('body').on('click', '.payu-read-more', function () {
		$(this).hide();
		var elementToShow = $(this).data('more');
		$('body #' + elementToShow).show();
	});

	if(window.location.hash === '#repayment'){
		setTimeout(function(){
			$('html, body').animate({
				scrollTop: $(".repayment-container").offset().top
			}, 1000);
		}, 500)
	}

	$(document).on('click', '#HOOK_PAYMENT .payment_module a.payu', function(e){
		if($(this).attr('href') === '') {
			init_sf();
			$(this).parent().next('.payment_module_content').show();
			return false;
		} else {
			return doubleClickPrevent(this);
		}
	});
	$('.repayment-options .payMethod:not(.payMethodDisable)').on('click', function(e){
		$('[name="transferGateway"]').val($(this).find('input').val());
	})
});

function openPayment(paymentId) {
	setTimeout(function () {
		$('body').find('#payment-option-' + paymentId).click();
	}, 500);
}
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
						var paymentContent = document.querySelector('[data-payment-open='+paymentElement+']');
						paymentContent.classList.toggle('payment_module_content--show');
					}
				});
			});

		document.querySelectorAll('.repayment-single').forEach(function (element) {
			element.addEventListener('click', function (ev) {
				ev.stopPropagation();

				var paymethod = $(element).parent().next('.additional-information').find('.payment-name').attr('data-pm');
				$('[name="payMethod"]').val(paymethod);
				$('.additional-information').hide();
				$(element).parent().next('.additional-information').show();


				$('[name="payment_id"]').val(ev.target.id.slice('15').replace('-container', ''));
			}, true);
		});

		document.querySelectorAll('.payu-payment-fieldset-1-6 .payment_module, .repayment-options').forEach(function (elm) {
			validateBeforeSubmitCardForm();
			validateBeforeSubmitGatewaysForm();
		});


		function activatePaymentButton() {
			var btnSubmit = document.querySelector('.pay-transfer-accept button');
			if(btnSubmit !== null) {
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
			if($('.repayment-options').length > 0 && $('.repayment-options').hasClass('has-sf') && $('[name="payMethod"]').val() == 'card' || $('.repayment-options').length == 0) {
				var paymentCardSubmit = document.querySelector('#payment-confirmation .btn, .repayment-options input[type="submit"], #secure-form-pay');
				if (paymentCardSubmit !== null) {
					paymentCardSubmit.addEventListener('click', function (e) {
						if($('#card-form-container').is(':visible')) {
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


		function payuGatewaysValidate() {
			var validateResponse = document.getElementById('transfer-response-box');
			var btn = document.querySelector('.pay-transfer-accept button');
			var form = document.querySelector('#paymentTransfer');

			var $currentGateway = $('input[name=transferGateway]');

			if($currentGateway.val() === '') {
				validateResponse.style.display = 'block';
				btn.setAttribute('disabled', '');
			} else {
				if($('.repayment-options').length == 0){
					form.submit()
				}
				else{
					$('#paymentTransfer').closest('form').submit();
				}
			}
		}

		$(document).on('click', 'input[name=transfer_gateway_id]', function () {
			resetAllGatewaysActive();

			var gatewayValue = this.value;
			var item = document.querySelector('#payMethodContainer-'+gatewayValue);
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
		} );


		// Polyfill from https://developer.mozilla.org/pl/docs/Web/JavaScript/Referencje/Obiekty/Object/assign
		"function"!=typeof Object.assign&&(Object.assign=function(n,t){"use strict";if(null==n)throw new TypeError("Cannot convert undefined or null to object");for(var r=Object(n),e=1;e<arguments.length;e++){var o=arguments[e];if(null!=o)for(var c in o)Object.prototype.hasOwnProperty.call(o,c)&&(r[c]=o[c])}return r});

		// Polyfill Array.from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/from#polyfill
		Array.from||(Array.from=function(){var r;try{r=Symbol.iterator?Symbol.iterator:"Symbol(Symbol.iterator)"}catch(t){r="Symbol(Symbol.iterator)"}var t=Object.prototype.toString,n=function(r){return"function"==typeof r||"[object Function]"===t.call(r)},o=Math.pow(2,53)-1,e=function(r){var t=function(r){var t=Number(r);return isNaN(t)?0:0!==t&&isFinite(t)?(t>0?1:-1)*Math.floor(Math.abs(t)):t}(r);return Math.min(Math.max(t,0),o)};return function(t){var o=Object(t),a=n(o[r]);if(null==t&&!a)throw new TypeError("Array.from requires an array-like object or iterator - not null or undefined");var i,u=arguments.length>1?arguments[1]:void 0;if(void 0!==u){if(!n(u))throw new TypeError("Array.from: when provided, the second argument must be a function");arguments.length>2&&(i=arguments[2])}var f=e(o.length);return function(r,t,n,o,e,a){for(var i=0;i<n||e;){var u=o(i),f=e?u.value:u;if(e&&u.done)return t;t[i]=a?void 0===r?a(f,i):a.call(r,f,i):f,i+=1}if(e)throw new TypeError("Array.from: provided arrayLike or iterator has length more then 2 ** 52 - 1");return t.length=n,t}(i,n(this)?Object(new this(f)):new Array(f),f,function(t,n){var o=t&&n[r]();return function(r){return t?o.next():n[r]}}(a,o),a,u)}}());




		init_sf();
		$('body').on('click', '.history_detail a', function(){
			setTimeout(function(){
				init_sf();
				validateBeforeSubmitCardForm();
				validateBeforeSubmitGatewaysForm();
			}, 4000)
		});

		function payuCardValidate() {

			hideMessageBox();
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

						showMessageBox(errorMessage);

						window.secureFormNumber.update({disabled: false});
						window.secureFormDate.update({disabled: false});
						window.secureFormCvv.update({disabled: false});
					}
				});
			} catch (e) {
				showMessageBox(e.message);
			}
		}
	});
})();

function init_sf(){
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

function showMessageBox(message) {
	var responseBox = document.getElementById('response-box');
	responseBox.innerHTML = message;
	responseBox.style.display = '';
}

function hideMessageBox() {
	var responseBox = document.getElementById('response-box');
	responseBox.innerHTML = '';
	responseBox.style.display = 'none';
}
