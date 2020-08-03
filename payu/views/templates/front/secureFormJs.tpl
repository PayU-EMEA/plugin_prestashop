{*
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
*}
<script>
    (function () {
        //Polifill from https://developer.mozilla.org/pl/docs/Web/JavaScript/Referencje/Obiekty/Object/assign
        if (typeof Object.assign != 'function') {
            Object.assign = function(target, varArgs) { // .length of function is 2
                'use strict';
                if (target == null) { // TypeError if undefined or null
                    throw new TypeError('Cannot convert undefined or null to object');
                }

                var to = Object(target);

                for (var index = 1; index < arguments.length; index++) {
                    var nextSource = arguments[index];

                    if (nextSource != null) { // Skip over if undefined or null
                        for (var nextKey in nextSource) {
                            // Avoid bugs when hasOwnProperty is shadowed
                            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                                to[nextKey] = nextSource[nextKey];
                            }
                        }
                    }
                }
                return to;
            };
        }

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
                lang: '{$lang}'
            }
        };

        secureFormOptions.profile = calculateProfile();
        secureFormOptions.config = Object.assign({}, secureFormOptions.config, secureFormOptions.profiles[secureFormOptions.profile]);


        var payu = PayU({$posId});

        var secureForms = payu.secureForms();
        var secureFormNumber = secureForms.add('number', secureFormOptions.config);
        secureFormNumber.render(secureFormOptions.elementFormNumber);
        var secureFormDate = secureForms.add('date', secureFormOptions.config);
        secureFormDate.render(secureFormOptions.elementFormDate);
        var secureFormCvv = secureForms.add('cvv', secureFormOptions.config);
        secureFormCvv.render(secureFormOptions.elementFormCvv);
        window.addEventListener('resize', secureFormResize);

        var payButton = document.getElementById('secure-form-pay');
        var responseBox = document.getElementById('response-box');
        var cardTokenInput = document.getElementById('card-token');

        payButton.addEventListener('click', function(event) {
            event.preventDefault();

            var isAcceptPayuConditions = document.getElementById('payuCondition').checked;

            if (!isAcceptPayuConditions) {
                showMessageBox('<strong>{l s='Please accept "Terms of single PayU payment transaction"' mod='payu'}</strong>');
                return;
            }

            hideMessageBox();
            cardTokenInput.value = '';
            secureFormNumber.update({ disabled: true });
            secureFormDate.update({ disabled: true });
            secureFormCvv.update({ disabled: true });

            try {
                payu.tokenize().then(function(result) {
                    if (result.status === 'SUCCESS') {
                        secureFormNumber.remove();
                        secureFormDate.remove();
                        secureFormCvv.remove();
                        cardTokenInput.value = result.body.token;
                        document.getElementById('waiting-box').style.display = '';
                        document.getElementById('card-form-container').style.display = 'none';
                        document.getElementById('payu-card-form').submit();
                    } else {
                        var errorMessage = "{l s='An error occurred while trying to use the card' mod='payu'}:<br>";
                        result.error.messages.forEach(function(error) {
                            errorMessage += '<strong>' + error.message + '<strong><br>';
                        });

                        showMessageBox(errorMessage);

                        secureFormNumber.update({ disabled: false });
                        secureFormDate.update({ disabled: false });
                        secureFormCvv.update({ disabled: false });
                    }
                });
            } catch(e) {
                showMessageBox(e.message);
            }
        });

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

    })();
</script>