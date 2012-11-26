# PayU plugin for Prestashop since 1.4.4  (alpha release)
-------
PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

This plugin is licensed under the GPL license.

**Note:** This is an alpha release and we are still working on plugin improvements.

## Table of Contents

[Prerequisites][1] <br />
<!--[Installation][2]-->
 * 
[Installing Manually][2.1]

<!--* [Installing from admin page][2.2]-->

[Configuration][3]
* [Configuration Parameters][3.1]


## Prerequisites

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.
* [XMLWriter][ext4] to wrap the libxml xmlWriter API.
* [XMLReader][ext5] that acts as a cursor going forward on the document stream and stopping at each node on the way.

## Installation

<!--There are two ways in which you can install the plugin:

* [manual installation][2.1] by copying and pasting folders from the repository
* [installation from the admin page][2.2]

See the sections below to find out about steps for each of the procedures.-->

### Installing Manually

To install the plugin, copy folders from the repository and activate the plugin on the administration page:

1. Copy folders from [plugin_prestashop][ext1] to your PrestaShop root folder on the server.
2. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
3. Go to **Modules** > **Payments & Gateways**.
4. Select **PayU** and click **Install**.


<!--### Installing from the administration page

PrestaShop allows you to install the plugin from the administration page. -->


## Configuration

To configure the PrestaShop plugin:

1. Copy folders from [plugin_prestashop][ext1] to your PrestaShop root folder on the server.
2. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
3. Go to **Modules** > **Payments & Gateways**.
4. Select **PayU** and click **Configure**.

### Configuration Parameters

The tables below present the descriptions of the configuration form parameters.

#### Main parameters

The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Test Mode On|Yes/No|When you are in the test mode, the transactions are only simulated and no real payments are made. Use the test mode to see how the transactions work.|
|Self-Return Enabled|Yes/No|When self-return is disabled, the payment must be confirmed manually.|
|Order Validity Time|24h/12h/6h/1h/30min|Specifies the time during which the order is valid in the PayU system. When validity time expires, the order is cancelled, and you are notified that the transaction failed.|
|Ship Abroad|Enabled/Disabled|When ship abroad is disabled, you can only choose the country of the shop for shipping.|
|OneStepCheckout Enabled|Yes/No|Specifies whether buying from cart via Payu is enabled.|

#### Parameters of test and production environments

The test environment is called *Sandbox* and you can adjust it separately from the production environment to see which configuration suits you the best.
<!--To check the values of the parameters below, go to **Administration Panel** > **My shops** > **Your shop** > **POS** and click the name of a given POS.
-->
**Important:** If you set the [**Test Mode On**][3.1.1] parameter to *Yes*, the transactions in your shop are only simulated. No real payments are made.

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Key|Unique MD5 key
|Second Key| MD5 key for securing communication|
|POS Auth Key|Transaction authorization key|

#### Settings of external resources

You can set external resources for the following:

| Parameter |Description | 
|:---------:|:-----------:|
|Payment button|URL address of the button image for OneStepCheckout|
|Payment logo|URL address of the logo image that is visible in the list of payment methods|
|Payment adverts|URL address of the PayU advertisement for your page|
|Payment accept|URL address of the *We accept PayU payments* banner|

<!--LINKS-->

<!--topic urls:-->

[1]: https://github.com/PayU/plugin_prestashop#prerequisites
[2]: https://github.com/PayU/plugin_prestashop#installation
[2.1]: https://github.com/PayU/plugin_prestashop#installing-manually
[2.2]: https://github.com/PayU/plugin_prestashop#installing-from-admin-page
[3]: https://github.com/PayU/plugin_prestashop#configuration
[3.1]: https://github.com/PayU/plugin_prestashop#configuration-parameters
[3.1.1]: https://github.com/PayU/plugin_prestashop#main-parameters
[3.1.2]: https://github.com/PayU/plugin_prestashop#parameters-of-production-and-test-environments
[3.1.3]: https://github.com/PayU/plugin_prestashop#settings-of-external-resources


<!--external links:-->

[ext1]: https://github.com/PayU/plugin_prestashop_144
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php
[ext4]: http://php.net/manual/en/book.xmlwriter.php
[ext5]: http://php.net/manual/en/book.xmlreader.php

<!--images:-->