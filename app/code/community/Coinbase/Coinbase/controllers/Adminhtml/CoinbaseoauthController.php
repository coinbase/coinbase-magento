<?php

class Coinbase_Coinbase_Adminhtml_CoinbaseoauthController extends Mage_Adminhtml_Controller_Action
{        
    
    public function redirectAction() {
    
      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");
      
      $clientId = Mage::getStoreConfig('payment/Coinbase/oauth_clientid');
      $clientSecret = Mage::getStoreConfig('payment/Coinbase/oauth_clientsecret');
      $redirectUrl = Mage::getUrl("coinbase_coinbase/redirect/oauth") . "?key=$_GET[key]";
      $oauth = new Coinbase_Oauth($clientId, $clientSecret, $redirectUrl);
      
      try {
        $tokens = $oauth->getTokens($_GET['code']);
      } catch (Exception $e) {
      
        ?><html><body>
          <p style='text-align: center; font-family: sans-serif;'>Sorry, but there was an error while connecting your account (<?php echo htmlentities($e->getMessage()); ?>). <a href='<?php echo $adminUrl; ?>'>Return to Magento Admin</a></p>
        </body></html><?php
        return;
      }
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', serialize($tokens))->cleanCache();
      
      // Required to make sure configuration saves
      Mage::app()->getStore()->resetConfig();
      
      // Success!
      $this->_redirectUrl(Mage::helper("adminhtml")->getUrl('adminhtml/system_config/edit/section/payment') . '#coinbase-coinbase');
    }
    
    public function disconnectAction() {
    
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientid', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientsecret', null)->cleanCache();
      
      // Required to make sure configuration saves
      Mage::app()->getStore()->resetConfig();

      $this->_redirectUrl(Mage::helper("adminhtml")->getUrl('adminhtml/system_config/edit/section/payment') . '#coinbase-coinbase');
    }
}