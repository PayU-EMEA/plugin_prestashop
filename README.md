# PayU plugin for Prestashop since 1.4.4
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**


## Table of Contents

* [Features](#features)
* [Prerequisites](#prerequisites) 
* [Installing](#installation)
* [Upgrading](#upgrade)
* [Configuration](#configuration)

## Features
The PayU payments Prestashop plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order (with discounts included)
* Cancelling a payment order
* Receive a payment order (when auto-receive is disable)
* Conducting a refund operation (for a whole or partial order)
* Display payment methods on Presta checkout summary page (only for Prestashop 1.5 and 1.6)

## Prerequisites

**Important:** This plugin works only with checkout points of sales (POS).

The following PHP extensions are required:

* [cURL][ext1] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext2] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.

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

## Upgrade

1. Update plugin files according to [Installing](#installation)
2. Go to **Advanced Parameters** > **Performance** adn click **Clear cache** 

## Configuration

To configure the PrestaShop plugin:

1. Go to the PrestaShop administration page [http://your-prestashop-url/admin].
2. Go to **Modules** > **Payments & Gateways**.
3. Select **PayU** and click **Configure**.


### Configuration Parameters

The tables below present the descriptions of the configuration form parameters.

#### Integration method
Works only Prestashop 1.5 and 1.6

| Parameter | Description | 
|:---------:|:-----------:|
|Payment methods displayed on Presta checkout summary page|If "No" then Prestashop will redirect to PayU payment page|


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

<!--LINKS-->

<!--external links:-->
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php

<!--images:-->
[img1]: https://raw.github.com/PayU/plugin_prestashop/master/readme_images/presta_pos_config.png
[img2]: https://raw.github.com/PayU/plugin_prestashop/master/readme_images/pos_configuration_keys.png