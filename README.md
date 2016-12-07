# mCASH plugin for Magento

## Background
This plugin has been developed by Klapp Media AS as a further development of the original plugin developed by Trollweb Solutions AS

## Prerequisites
You must have a mCASH merchant account to use this plugin. Sign up here https://my.mca.sh/mssp/signup/ .

## Configuration
Log into your Magento Store's Admin Panel and navigate to **System > Configuration > Sales > Payment Methods > mCASH **.

The configration table needs

* Merchant ID ( It was assigned to you when you signed up at https://my.mca.sh/mssp/signup/ ).
* User ( merchant used id. You need to create that on https://my.mca.sh/mssp/ ).
* Copy the Public Key on the settings screen and paste it in to your settings on https://my.mca.sh/mssp/

## Capture
The plugin does not do capture automaticly, but first when you press "Send invoice".

## License
This projected is licensed under the terms of the MIT license.

## Issues
* There is a known issue on sites that run php compilation. Recompile your code if you are experiencing any errors after installation
 
