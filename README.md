# PayU plugin for Prestashop since 1.4.4
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

## Table of Contents

* [Features](#features)
* [Prerequisites](#prerequisites) 
* [Installing](#installing-manually)
* [Configuration](#configuration)
    * [Business area](#business-area)
    * [Configuration Parameters (Poland)](#configuration-parameters-poland)
    * [Configuration Parameters (Romania, Turkey, Russia, Ukraine, Hungary)](#configuration-parameters-romania-turkey-russia-ukraine-hungary)

## Features
The PayU payments Prestashop plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order (with discounts included)
* Cancelling a payment order
* Conducting a refund operation (for a whole or partial order)


## Prerequisites

**Important:** This plugin works only with checkout points of sales (POS).

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.

## Installation

### Option 1 - recommended for users without FTP access to their PrestShop installation

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_prestashop) to local directory as zip.
2. Unzip locally downloaded file
3. **Create zip archive of payu directory**
4. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
5. Go to **Modules** > **Modules**.
6. **Add new module** and point archive contained plugin (created at point 3)
7. Load the plugin

### Option 2 - recommended for users with FTP access to their PrestaShop installation
1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_prestashop) to local directory as zip.
2. Unzip locally downloaded file
3. Upload **'payu'** directory from your computer to **'modules'** catalog of your PrestaShop installation.

## Configuration

To configure the PrestaShop plugin:

1. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
2. Go to **Modules** > **Payments & Gateways**.
3. Select **PayU** and click **Configure**.

**Important:** In order to enable the customers to make payments with OneStepCheckout, you must go to **Preferences > Orders** and set **Enable guest checkout** to **Yes**.

### Business area

This section define merchant business area. Other seection depends on this setup. It means that there might be different merchant configuration for Poland and Turkey merchant. 
Details are described in next sections.


### Configuration Parameters (Poland)

The tables below present the descriptions of the configuration form parameters.

#### Main parameters

The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Self-Return Enabled|Yes/No|If self-return is disabled, the payment must be confirmed manually.|
|Order Validity Time|24h/12h/6h/1h/30min|Specifies the time during which the order is valid in the PayU system. When validity time expires, the order is cancelled, and you are notified that the transaction failed.|
|OneStepCheckout Enabled|Yes/No|Specifies whether buying from cart via Payu is enabled. <br><br> **Important:** In order to enable the customers to make payments with OneStepCheckout, you must go to **Preferences > Orders** and set **Enable guest checkout** to **Yes**.|

#### POS Parameters

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Second Key|MD5 key for securing communication|
|OAuth - client_id|client_id for OAuth|
|OAuth - client_secret|client_secret for OAuth|

### Configuration Parameters (Romania, Turkey, Russia, Ukraine, Hungary)

The tables below present the descriptions of the configuration form parameters. The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Merchant|-|Unique ID of the merchant|
|Secret Key|-|Key for securing communication|
|IPN|On/Off|**Instant Payment Notification** makes possible the automated processing of each authorized order in the online payment system, being a link between the PayU servers and your servers. When your shop receives an _IPN_ for an order, if _IPN_ is set to _On_, the status of the order will become _Payment accepted_. If _IPN_ is set to _Off_ and your merchant account settings in PayU are in accordance with this, the status of the order will become _Payment accepted_ when the buyer is redirected from PayU payment page back to your shop.|
|IPN URL|-|After an order gets authorized and approved, the PayU sends a data structure containing all the order related info to this URL on your system.|
|IDN|On/Off|**Instant Delivery Notification** -- This request will be sent by your shop to PayU when you click _Confirm delivery_ in the order page. Confirming delivery triggers the capture of the order amount from the credit card. _Confirm delivery_ will be available for an order only if its status is _Payment accepted_ and _IDN_ is _On_. If PayU confirms the success of the _IDN_, the order status in your shop will be _Delivered_. If _IDN_ fails for some reason, the status of the order will remain unchanged.|
|IRN|On/Off|**Instant Refund/Reverse Notification** makes it possible for you to automate the sending of reverse/refund requests for orders paid through PayU, directly from your shop's order page. You may perform multiple refunds if your merchant account in the PayU platform allows it. If PayU confirms the success of the IRN, the status of the order in your shop will be _Refund_. If IRN fails for some reason, the status of the order will remain unchanged.|

**Notes:**

- The parameters must be set in accordance with your merchant account's settings in the PayU platform.
- IPN, IDN and IRN will automatically change the status of the orders. When you manually change the status of the order, your shop will not send any notification to PayU.

<!--LINKS-->

<!--external links:-->

[ext1]: https://github.com/PayU/plugin_prestashop_144/tree/refactoring
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php

<!--images:-->
