coinbase-magento
================

Accept Bitcoin on your Magento-powered website with Coinbase.

Download the plugin here: https://github.com/coinbase/coinbase-magento/archive/master.zip

Installation
-------

Download the plugin and copy the 'app' folder to the root of your Magento installation.

If you don't have a Coinbase account, sign up at https://www.coinbase.com/merchants. Coinbase offers daily payouts for merchants in the United States. For more infomation on setting up payouts, see https://www.coinbase.com/docs/merchant_tools/payouts.

After installation, open Magento Admin and navigate to System > Configuration > Payment Methods:

![Configuration](http://i.imgur.com/m0x0C5M.png)
![Payment Methods](http://i.imgur.com/Dr6FbFV.png)

Scroll down to 'Coinbase' and follow the instructions. If you can't find 'Coinbase', try clearing your Magento cache.

![Setup](http://i.imgur.com/VkFmy5a.png)

Custom events
-------

The plugin sends one event - 'coinbase_callback_received' when a callback is received. You can use this event to implement custom functionality on your Magento store.
