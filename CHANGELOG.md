## 3.3.1
* Fix empty array handling on pay methods retrieval
* Fix PHP notice when trying to read empty value in templates

## 3.3.0
* Added credit widget excluded pay types configuration option
* Added credit widget currency and language parameters
* Removed credit widget amount and pay method type presentation conditions
* Changed configuration labels and their translations to accommodate BNPL payment methods
* Added new credit payment widget screenshot and other credit payment-related updates in README
* Removed the credit widget from checkout payment methods list
* Added support for Klarna CZK, Klarna EUR, Klarna HUF, Twisto CZK, Twisto Pay in 3, and PayPo RO payment methods
* Added an ability to display Twisto Pay in 3 as a separate payment method
* Separated credit promotion configuration toggle into credit widget and separate installments toggles
* Removed an unused dp payment method
* Added margin and a translation to the payment button on the retry payment page

## 3.2.21
* Update Klarna logo
* Update Twisto logo
* Remove load unnecessary script

## 3.2.20
* Fixed credit logotypes
* Added credit logotypes for Prestashop 1.6
* Added option "Save logs"

## 3.2.19
* Klarna as separate payment method
* PayPo as separate payment method

## 3.2.18
* Fixed #323 - added validation when creating refunds
* Fixed multiple assignment payu order to Presta order

## 3.2.17
* Fixed repayment when PS_PURCHASE_MINIMUM non zero
* Update logos
* Cleanup

## 3.2.16
* Fixed payment without bank list
* Added save info about selected method

## 3.2.15
* IP address from Prestashop Tools
* SDK update

## 3.2.14
* Fixed warnings in cart
* Fixed warnings in payu logger
* Fixed open_basedir restriction
* Sends more data for better detected frauds

## 3.2.13
* Fixed show separated Twisto [Prestashop 1.6]
* Fixed pay with Bank List and Card Widget [Prestashop 1.6]

## 3.2.12
* Fixed payment for non classic themes

## 3.2.11
* Cleanup hooks names (#312)
* Add option 'Without changing status' for CANCELED notification

## 3.2.10
* Fixed incompatible with php <7.1

## 3.2.9
* Optimized pay methods retrieve

## 3.2.8
* Fixed installment widget display on product and checkout page
* Changed styling of installment section on checkout page
* Cleanup of credit amount thresholds

## 3.2.7
* Fixed repayment for guest (#302)
* Fixed generate repayment link for PrestaShop 1.7.6.X

## 3.2.6
* Compatible with Presta 8
* Cleanup translate

## 3.2.5
* Fixed missing installment widget presentation

## 3.2.4
* Fixed transfer payment

## 3.2.3
* Revert prevent double click for Presta 1.6
* Cleanup payment process

## 3.2.2
* Fix #303 (PHP Deprecated: str_replace())
* Fixed display PayU as payment method for non PayU orders

## 3.2.1
* Better load js/css for PrestaShop 1.7
* Not show DISABLED paymethods

## 3.2.0
* New payment retry system
* Move select bank and card form from separate page to checkout
* Update Visa logotype

## 3.1.14
 * Apply new credit mini widget with automatic configuration based on posId

## 3.1.13
 * Return to Prestashop standard order-confirmation page

## 3.1.12
 * BLIK as separate payment method
 * Logotypes update
 * Fix #281 logger is active when debug mode is enabled
 * Fix 500 for refund notification
 * Fix select pay method

## 3.1.11
 * Fix sending selected credit payment methods in order

## 3.1.10
 * Added credit section to order for installment and delayed payment methods

## 3.1.9
 * Quick fix #279

## 3.1.8
 * Fix #277
 * Update SDK (fix PHP 8)

## 3.1.7
 * Add delayed payment with Twisto support
 * Remove delayed payment with Monedo support

## 3.1.6
 * Fix classname conflict

## 3.1.5
 * Add option "Pay by click on bank icon button"
 * Card widget migrate to new version of SecureForm

## 3.1.4
 * Show ApplePay on payment page
 * Fix #263 map lang gb to en

## 3.1.3
 * Add reason info when refund error
 * Fix PHP: Notice

## 3.1.2
 * Fix translate

## 3.1.1
 * Fix covering other modules in admin panel
 * Fix Cache'ing paymethods


## 3.1.0
 * Separate card payment
 * Card payment by widget

## 3.0.22
 * Fix Cache'ing Mini installment widget 
 * Fix #260

## 3.0.21
 * Fix PHP: Notice

## 3.0.20
 * Fix #259 (minification widget-installments)

## 3.0.19
 * Fix #256

## 3.0.18
 * New copy for DP english version

## 3.0.17
 * New copy for DP

## 3.0.16
 * Fixed submitting payment for Prestashop v1.7.5.0

## 3.0.15
 * Fixed retry payment for guests

## 3.0.14
 * Added status change control
 * Separated configuration for installments

## 3.0.13
 * Fixed configuration form generates notices (lukasz-zaroda)
 * Fixed generate urls

## 3.0.12
 * Fix image path for logo
 * Fixed min & max credit edge cases

## 3.0.11
 * Fixed payment methods icon alignment
 * Fixed css class naming for mini installment widget displaying

## 3.0.10
 * Promoting credit payment methods feature: https://github.com/PayU/plugin_prestashop/tree/master#promowanie-p%C5%82atno%C5%9Bci-ratalnych-i-odroczonych
 * Add missing nofilter after display hook
 
## 3.0.9
 * Remove expose version
 * Update SDK

## 3.0.8
 * Fixed http 500 for COMPLETED notification

## 3.0.7
 * Update Private Policy

## 3.0.6
 * Minor fixes

## 3.0.5
 * Fix for php 5.4
 * Update CTA text

## 3.0.4
 * Place OCR after Order
 * Add paymethods order

## 3.0.3
 * Add sandbox
 * Update SDK

## 3.0.2
 * Add thank you page after return from PayU
 * Fix IE11 issue
 * Fix for PHP 5.3
 * Fix notice on payu payment page
 * Fix save settings

## 3.0.1
 * Fix for PrestaShop 1.6

## 3.0.0
 * Add support for PrestaShop 1.7
 * Remove support for PrestaShop 1.4 and 1.5
 * Migrate configure page to bootstrap
 * Cleanup code

## 2.5.1
 * Fix disable payment methods by currency for Prestashop 1.4

## 2.5.0
 * Add retry payment support
 * Fix for Prestashop 1.4 and 1.5

## 2.4.2
 * Add czech language support

## 2.4.1
 * Fix show error on pay methods page
 * Cleanup code

## 2.4.0
 * New integration method - payment methods displayed on Prestashop checkout summary page
 * Cleanup code

## 2.3.2
 * Remove ePayment support
 * Remove unused parameters
 * Cleanup code

## 2.3.1
 * Add multicurrency support

## 2.3.0
 * Update PayU SDK
 * Add support for OAuth

## 2.2.3
 * beautiful pay buttons
 * fix return to OPC (one Page Checkout)

## 2.2.2
 * Fix for accept/reject waiting_for_confirmation payment 
 
## 2.1.9 / 2.2.0
 * Remove invoice and shipping information on summary page
 * Prevent mass click on pay button
 * Compatible with Advanced UE compliance module
 * Update PayU SDK
 * Update logotypes
 * Cleanup code

## 2.1.6
 * Basic logging feature added for notification path tracking
 * Multiply orders and status changes bug fixed

## 2.1.5
 * Fixed return to payment methods button for Prestashop 1.6+
 * Added translations for Prestashop 1.6+
 * Fixed bugs

## 2.1.4
 * Fixed return to payment methods button for Prestashop 1.6+
 * Added translations for Prestashop 1.6+
 * Fixed bugs

## 2.1.4
* Fixed validityTime

## 2.1.3
* Payment id visible in admin panel after payment is completed

## 2.1.2
* Notifications in Presta 1.4.4 fixed

## 2.1.1
* Openpayu_php SDK 2.1 compatible

## 2.0.4
* Cart rules included

## 2.0.3
* Prestashop 1.4.4 compatibility fixed

## 2.0.2
* Order statuses change

## 2.0.1

* Order V2

## 1.9.12

* Fixed shop domain url
* Fixed the type of protocol of the all images

## 1.9.11

* Fixed status change after notification

## 1.9.10

* Fixed ShippingType parser

## 1.9.9

* Fixed XSS on error and success pages

## 1.9.8

* Corrected source code references to language versions of the graphics

## 1.9.7

* Added fix for Fatal error (Order -> conversion_rate is empty), below Prestashop version 1.5

## 1.9.6

* Added fix for free shipping in cart rules. Available only Prestashop since version 1.5

## 1.9.5

* Added support for the invoices [enabled/disabled] on summary page
* Added support for free shipping in cart rules

## 1.9.4

* Fixed problem with duplicated payments
* Fixed wrong success redirect

## 1.9.3

* Changed retrieving value of orderId in orderNotifyRequest function

## 1.9.2

* Fixed updating of choosen shipping method
* Added paid amount in payments

## 1.9.1

* Changed OneStepCheckout Enabled only for logged customers

## 1.9

* Added creating order's statement before redirect to PayU
* Removed creating order's statement after redirect from PayU

## 1.8.1

* Fixed setting reject payment status
* Added statuses translation for polish language
* Fixed discounts for orders

## 1.8

* Fixed order data update
* Fixed tax value of shipping methods
* Added invoices billing information in OrderCreateRequest
* Fixed status changing in Self-Returns
* Added manage payment statuses in configuration page

## 1.7

* Fixed translations of messages
* Fixed null Tax rates
* Fixed empty DOCUMENT parameter in payment_notify.php

## 1.6

* Changed the address of the module construction method
* Redirect to the error page after cancelling the transaction

## 0.1.5.1

* Fixed static shipping methods data

## 0.1.5

* The plugin is compatible with PrestaShop 1.5

## 0.1.4

* Added customer information in orderCreateRequest
* Fixed duplicate sessionId entries

## 0.1.3

* Changed CountryCode value to default in orderCreateRequest
* Changed extension name

## 0.1.2

* SDK 0.1.9.1 compatible
* Added new shopping process flow

Notice: Required to clear the shop cache after update extension.

## 0.1.1

* SDK 0.1.9.1 compatible
* Fixed invalid Order url address
* Added order number in transaction description
