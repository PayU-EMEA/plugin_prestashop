[**Wersja polska**][ext0]

# PayU plugin for Prestashop 1.6 and 1.7
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

Note: Plugin [version 2.x](https://github.com/PayU/plugin_prestashop/tree/2.x) supports PrestaShop versions 1.4 and 1.5, but is not developed any more.

## Table of Contents

* [Features](#features)
* [Prerequisites](#prerequisites) 
* [Installing](#installation)
* [Upgrading](#upgrade)
* [Configuration](#configuration)
* [More on features](#more-on-features)
    * [Multi-currency](#multi-currency)
    * [Payment method display](#payment-method-display)
    * [Payment retry](#payment-retry)

## Features
The PayU payments Prestashop plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

Plugin version 3.x supports PrestaShop versions 1.6 and 1.7

| Feature | PrestaShop 1.6 | PrestaShop 1.7 |
|---------|:-----------:|:-----------:|
| Creating a payment order (with discounts included) | :white_check_mark: | :white_check_mark: |
| Capturing a payment order (when auto-capture is disabled) | :white_check_mark: | :white_check_mark: |
| Conducting a refund operation (whole or partial) | :white_check_mark: | :white_check_mark: |
| Displaying payment methods on Presta checkout summary page | :white_check_mark: | :white_check_mark: |
| Payment retry for cancelled payments | :white_check_mark: | :white_check_mark: |
| Multi-currency support | :white_check_mark: | :white_check_mark: |

More information on the features can be found in the [More on features](#more-on-features) section

**All instructions regard PrestaShop 1.6, for versions 1.7 corresponding options should be used**.

## Prerequisites

**Important:** This plugin works only with 'REST API' (Checkout) points of sales (POS).

The following PHP extensions are required:

* [cURL][ext1] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext2] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.

## Installation

### Option 1 
**recommended for users without FTP access to their PrestShop installation**

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_prestashop) to local directory as zip.
1. Unzip locally downloaded file
1. **Create zip archive of payu directory**
1. Go to the PrestaShop administration page [http://adres-sklepu/adminxxx].
1. Go to 'Modules and Services' > 'Modules and Services'.
1. Use 'Add a new module' option and point the archive containing the plugin (created in step 3)
1. Load the plugin

### Option 2
**recommended for users with FTP access to their PrestaShop installation**

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_prestashop) to local directory as zip.
1. Unzip locally downloaded file
1. Upload **'payu'** directory from your computer to **'modules'** catalog of your PrestaShop installation.

## Upgrade

1. Update plugin files according to [Installing](#installation)
1. Go to do 'Modules and Services' > 'Modules and Services' - automated upgrade will be performed if required  
1. Go to **Advanced Parameters** > 'Performance' and click 'Clear cache' 

## Configuration

To configure the PrestaShop plugin:

1. Go to the PrestaShop administration page [http://adres-sklepu/adminxxx].
1. Go to 'Modules and Services' > 'Modules and Services'.
1. Search and select 'PayU' and click 'Configure'.

### Integration method
(works only Prestashop 1.5 and 1.6)

| Parameter | Description | 
|:---------:|:-----------:|
|Payment methods displayed on PrestaShop checkout summary page | **Yes** - payment methods displayed on PrestaShop checkout page <br>**No** - redirection to PayU after order is placed |


#### POS Parameters

For each currency defined in Presta please configure the below parameters.

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Second Key|MD5 key for securing communication|
|OAuth - client_id|client_id for OAuth|
|OAuth - client_secret|client_secret for OAuth|

##### Exemplary configuration

Presta:

![presta_pos_config][img1]

POS configuration in PayU merchant panel:

![pos_configuration_keys][img2]

### Payment status mapping
Mapowanie statusów płatności w PayU na statusy w skepie PrestaShop

| Name | PayU payment status | Default value in Presta | 
|---------|-----------|-----------|
| Pending status | `NEW` and `PENDING` | PayU payment started |
| Waiting for confirmation | `WAITING_FOR_CONFIRMATION` and `REJECTED` | PayU payment awaits for reception |
| Complete status | `COMPLETED` | Payment accepted |
| Canceled status | `CANCELED` | Canceled |

## More on features

### Multi-currency
POS in PayU system has only one currency defined. Therefore to accept payments in more currencies, POS for each currency has to be separately configured.

### Payment method display
When **Payment methods displayed on Presta checkout summary page** parameter is set to `Yes` payment method icons will be displayed directly within PrestaShop page when 'PayU with PayU' button is clicked.
The icons are displayed basing on POS configuration.  

![payment_methods][img3]

After payment method icon is selected and 'I confirm my order' button clicked, the buyer is redirected to bank or PayU card form.  

### Payment retry
When payment fails (i.e. is canceled), the buyer can pay again.
Following criteria should be met to enable payment retry:
* payment has to have CANCELED status in PayU
* order status in PrestaShop has to be in line with status configured for 'Canceled status'

If the criteria are met, the buyer will see a retry option on Order details screen.  

![retry_payment][img4]

All PayU payments created for a PrestaShop order are displayed on Order screen in PrestaShop admin panel. 

<!--LINKS-->

<!--external links:-->
[ext0]: README.EN.md
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php
[ext3]: https://github.com/PayU/plugin_prestashop

<!--images:-->
[img1]: readme_images/presta_pos_config.png
[img2]: readme_images/pos_configuration_keys.png
[img3]: readme_images/bramki_platnosci.png
[img4]: readme_images/ponow_platnosc.png
