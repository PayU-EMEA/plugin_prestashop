# PayU plugin for Prestashop since 1.4.4  (alpha release)
-------
``This plugin is released under the GPL license.``

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

**Note:** This is an alpha release and we are still working on plugin improvements.

## Table of Contents


<!--topic urls:-->


[3]: #configuration
[3.1]: #business-area
[3.2]: #configuration-parameters-poland
[3.3]: #configuration-parameters-romania-turkey-russia-ukraine-hungary
[3.4]: #settings-of-external-resources
[Prerequisites](#prerequisites) <br />
<!--[Installation][#installation]-->
 * 
[Installing Manually](#installing-manually)

<!--* [Installing from admin page][#installing-from-admin-page]-->

[Configuration](#configuration)
* [Business area](#business-area)
* [Configuration Parameters (Poland)](#configuration-parameters-poland)
* [Configuration Parameters (Romania, Turkey, Russia, Ukraine, Hungary)](#configuration-parameters-romania-turkey-russia-ukraine-hungary)
* [Settings of external resources](#settings-of-external-resources)

## Prerequisites

**Important:** This plugin works only with checkout points of sales (POS).

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.
* [XMLWriter][ext4] to wrap the libxml xmlWriter API.
* [XMLReader][ext5] that acts as a cursor going forward on the document stream and stopping at each node on the way.

## Installation

<!--There are two ways in which you can install the plugin:

* [manual installation](#installing-manually) by copying and pasting folders from the repository
* [installation from the admin page](#installing-from-admin-page)

See the sections below to find out about steps for each of the procedures.-->

### Installing Manually

To install the plugin, copy folders from the repository and activate the plugin on the administration page:

1. Download plugin from [the plugin repository][ext1] to local directory.
2. Create zip archive of internal directory (payu) which contains module
3. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
4. Go to **Modules** > **Modules**.
5. **Add new module** and point archive contained plugin
6. Load the plugin

<!--### Installing from the administration page

PrestaShop allows you to install the plugin from the administration page. -->


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
|Ship Abroad|Enabled/Disabled|If ship abroad is disabled, you can only choose the country of the shop for shipping.|
|OneStepCheckout Enabled|Yes/No|Specifies whether buying from cart via Payu is enabled. <br><br> **Important:** In order to enable the customers to make payments with OneStepCheckout, you must go to **Preferences > Orders** and set **Enable guest checkout** to **Yes**.|

#### Parameters of test and production environments

**Important:** There is no test environment (*Sandbox*) for this version of module and you can set Test Payment method in your POS settings in the PayU system, before you deploy your shop to production enivronment.

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Second Key| MD5 key for securing communication|

### Configuration Parameters (Romania, Turkey, Russia, Ukraine, Hungary)

The tables below present the descriptions of the configuration form parameters.

#### Main parameters

The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Merchant|-|Unique ID of the merchant|
|Secret Key|-|Key for securing communication|
|IPN|On/Off|Instant Payment Notification makes possible the automated processing of each authorized order in the online payment system, being a link between the PayU servers and your servers. This notification method will allow the retrieval of transaction data in order to be processed in your own order management system.|
|IPN URL|-|After an order gets authorized and approved, the PayU server sends a data structure containing all the order related info to a preset URL on your system. The data is sent through HTTP POST.|
|IDN|On/Off|The Instant Delivery Notification facilitated automatic delivery confirmations from your system directly to the PayU system which automatically registers these confirmations on the PayU servers. As soon as your orders made to the PayU system are confirmed, a POST must be sent through your administration system to a URL provided by PayU, containing the identification data for transaction about to be confirmed.|
|IRN|On/Off|Instant Refund/Reverse Notification makes it possible for you to automate the sending of reverse/refund requests for orders paid through PayU, directly from the order management application.|


### Settings of external resources

You can set external resources for the following:

| Parameter |Description | 
|:---------:|:-----------:|
|Payment button|URL address of the button image for OneStepCheckout|
|Payment logo|URL address of the logo image that is visible in the list of payment methods|
|Payment adverts|URL address of the PayU advertisement for your page|
|Payment accept|URL address of the *We accept PayU payments* banner|

<!--LINKS-->

<!--external links:-->

[ext1]: https://github.com/PayU/plugin_prestashop_144/tree/refactoring
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php
[ext4]: http://php.net/manual/en/book.xmlwriter.php
[ext5]: http://php.net/manual/en/book.xmlreader.php

<!--images:-->
