# mCASH plugin for Magento

## Background
This plugin has been developed by Trollweb Systems AS (Trollweb.no). It is currently not being maintained.

## Prerequisites
You must have a mCASH merchant account to use this plugin. Sign up here https://my.mca.sh/mssp/signup/ .


## Installation

This plugin is not yet distributed with Magento Connect. Do the following steps to install it.

Goto https://github.com/mcash/mcash-magento/tree/master/build/dist and download the file mcash-magento-1.0.2.tgz (download as RAW)

**From the Magento Connect Manager:**

Log into your Magento Store's Admin Panel and navigate to **System > Magento Connect > Magento Connect Manager**.

Under "Direct master file upload" choose the file mcash-magento-1.0.2.tgz that you downloaded and press upload.

![Magento Connect Manager](https://raw.githubusercontent.com/mcash/mcash-magento/master/docs/magento_connect_manager.png "Magento Connect Manager")

If you need to reinstall or upgrade to a later version, then uninstall the old installation first.

## Configuration
Log into your Magento Store's Admin Panel and navigate to **System > Configuration > Sales > Payment Methods > mCASH **.

![mCASH Configuration example](https://raw.githubusercontent.com/mcash/mcash-magento/master/docs/mcash_config_example.png "mCASH Configuration example")

The configration table needs

* Merchant ID ( It was assigned to you when you signed up at https://my.mca.sh/mssp/signup/ ).
* User ( merchant used id. You need to create that on https://my.mca.sh/mssp/ ).
* POS ID ( you can select anything you want, arguably, magento is a fitting name).
* Private Key. ( The corresponding Public Key needs to be set in https://my.mca.sh/mssp/ . They key pair can be created on https://my.mca.sh/mssp/ or you can create them yourself [Explained here](#KeyGen) .


## Capture
The plugin does not do capture automaticly, but first when you press "Send invoice".

## Refund
The plugin does not have native support for refund, however it is easy to do at https://my.mca.sh/mssp .

### <a name="KeyGen"></a>Private/Public Key pair generation
They key pair generation can be done at https://my.mca.sh/mssp/ , if you prefer to do it yourself and are using Linux or Mac OS X, you can generate the key pair with the follwoing commands in a terminal:

```
openssl genrsa -out private.pem.txt 2048
openssl rsa -in private.pem.txt -pubout -out public.pem.txt
```

## License
This projected is licensed under the terms of the MIT license.


## Developer
In order to generate the master file under build/dist/ clone git repo and do
```
composer install
./script/package
```


## Issues
* Does not yet support shopping from smart phone.
* Translation to Norwegian is not in place yet.
 
