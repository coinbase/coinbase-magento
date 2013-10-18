<?php

class Coinbase_Coinbase_OauthController extends Mage_Core_Controller_Front_Action
{        
    
    public function redirectAction() {
    
      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");
      
      $clientId = Mage::getStoreConfig('payment/Coinbase/oauth_clientid');
      $clientSecret = Mage::getStoreConfig('payment/Coinbase/oauth_clientsecret');
      $redirectUrl = Mage::getUrl('coinbase_coinbase'). 'oauth/redirect/?after=' . urlencode($_GET['after']);
      $oauth = new Coinbase_Oauth($clientId, $clientSecret, $redirectUrl);
      
      $codePortion = preg_replace("[^a-zA-Z0-9/]", "", $_GET['after']); // Make sure it's safe
      $adminUrl = Mage::getUrl("adminhtml/system_config/edit/section/payment") . $codePortion . '#coinbase-coinbase';
      
      try {
        $tokens = $oauth->getTokens($_GET['code']);
      } catch (Exception $e) {
      
        ?><html><body>
          <p style='text-align: center; font-family: sans-serif;'>Sorry, but there was an error while connecting your account (<?php echo htmlentities($e->getMessage()); ?>). <a href='<?php echo $adminUrl; ?>'>Return to Magento Admin</a></p>
        </body></html><?php
        return;
      }
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', $tokens);
      
      // Success!
      $this->_redirectUrl($adminUrl);
    }
    
    public function disconnectAction() {
    
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientid', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientsecret', null);
      
      $codePortion = preg_replace("[^a-zA-Z0-9/]", "", $_GET['after']); // Make sure it's safe
      $adminUrl = Mage::getUrl("adminhtml/system_config/edit/section/payment") . $codePortion . '#coinbase-coinbase';
      $this->_redirectUrl($adminUrl);
    }
}