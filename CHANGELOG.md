##3.0.2
 * Add thank you page after return from PayU
 * Fix IE11 issue
 * Fix for PHP 5.3
 * Fix notice on payu payment page
 * Fix save settings

##3.0.1
 * Fix for PrestaShop 1.6

##3.0.0
 * Add support for PrestaShop 1.7
 * Remove support for PrestaShop 1.4 and 1.5
 * Migrate configure page to bootstrap
 * Cleanup code

##2.5.1
 * Fix disable payment methods by currency for Prestashop 1.4

##2.5.0
 * Add retry payment support
 * Fix for Prestashop 1.4 and 1.5

##2.4.2
 * Add czech language support

##2.4.1
 * Fix show error on pay methods page
 * Cleanup code

##2.4.0
 * New integration method - payment methods displayed on Prestashop checkout summary page
 * Cleanup code

##2.3.2
 * Remove ePayment support
 * Remove unused parameters
 * Cleanup code

##2.3.1
 * Add multicurrency support

##2.3.0
 * Update PayU SDK
 * Add support for OAuth

##2.2.3
 * beautiful pay buttons
 * fix return to OPC (one Page Checkout)

##2.2.2
 * Fix for accept/reject waiting_for_confirmation payment 
 
##2.1.9 / 2.2.0
 * Remove invoice and shipping information on summary page
 * Prevent mass click on pay button
 * Compatible with Advanced UE compliance module
 * Update PayU SDK
 * Update logotypes
 * Cleanup code

##2.1.6
 * Basic logging feature added for notification path tracking
 * Multiply orders and status changes bug fixed

##2.1.5
 * Fixed return to payment methods button for Prestashop 1.6+
 * Added translations for Prestashop 1.6+
 * Fixed bugs

##2.1.4
 * Fixed return to payment methods button for Prestashop 1.6+
 * Added translations for Prestashop 1.6+
 * Fixed bugs

##2.1.4
* Fixed validityTime

##2.1.3
* Payment id visible in admin panel after payment is completed

##2.1.2
* Notifications in Presta 1.4.4 fixed

##2.1.1
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
