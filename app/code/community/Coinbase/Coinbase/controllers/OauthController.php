<?php

class Coinbase_Coinbase_OauthController extends Mage_Core_Controller_Front_Action
{        
    
    public function redirectAction() {

      return "TEST";
    
      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");
      
      $clientId = Mage::getStoreConfig('payment/Coinbase/oauth_clientid');
      $clientSecret = Mage::getStoreConfig('payment/Coinbase/oauth_clientsecret');
      $redirectUrl = Mage::getUrl('coinbase_coinbase'). 'oauth/redirect/';
      $oauth = new Coinbase_Oauth($clientId, $clientSecret, $redirectUrl);
      
      try {
        $tokens = $oauth->getTokens($_GET['code']);
      } catch (Exception $e) {
        return "Could not authenticate.";
      }
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', $tokens);
      
      $adminUrl = Mage::getUrl('admin/system_config/edit/section/payment');
      
      return "<html><body><p style='text-align: center; font-family: sans-serif;'><b>Success!</b> Your account was connected to the Magento Coinbase plugin. You are now ready to accept Bitcoin on your website. Make sure the Coinbase plugin is enabled under Magento Admin > System > Configuration > Payment Methods > Coinbase.<a href='$adminUrl'>Return to Magento Admin</a></p></body></html>";
    }
    
    public function disconnectAction() {
    
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientid', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientsecret', null);
      return '<script type="text/javascript">alert("Coinbase account removed.");</script><meta http-equiv="refresh" content="0;url='.Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/payment").'"/> ';
    }
}