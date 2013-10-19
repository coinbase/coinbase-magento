<?php 
class Coinbase_Coinbase_Block_Oauth extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");

      $tokens = Mage::getStoreConfig('payment/Coinbase/oauth_tokens');
      $clientId = Mage::getStoreConfig('payment/Coinbase/oauth_clientid');
      $clientSecret = Mage::getStoreConfig('payment/Coinbase/oauth_clientsecret');

      $key = Mage::getSingleton('adminhtml/url')->getSecretKey("coinbaseoauth","redirect"); 
      $redirectUrl = Mage::getUrl("coinbase_coinbase/redirect/oauth") . "?key=$key";
      $disconnectUrl = Mage::helper("adminhtml")->getUrl('*/coinbaseoauth/disconnect');
      $oauth = new Coinbase_Oauth($clientId, $clientSecret, $redirectUrl);
      
      $output = "<a id='coinbase-coinbase'></a>";
      
      if($clientId == null || $clientSecret == null) {
      
        $redirectUrlNoCode = Mage::getUrl("coinbase_coinbase/redirect/oauth");
        return $output . "<b>No merchant account connected.</b><br>To start accepting payments, you need to connect a merchant account.<br><br>
        First, create an account on <a href='https://coinbase.com/'>Coinbase</a> if you don't have one. For information on setting up your bank account for daily payouts, please visit <a href='https://coinbase.com/docs/merchant_tools/payouts'>https://coinbase.com/docs/merchant_tools/payouts</a>.<br><br>
        Once you have set up your account, <a href='https://coinbase.com/oauth/applications/new' target='_blank'>click here</a> to create a new OAuth2 application and enter the following information:<br><ul><li><b>Name:</b> a name for this Magento installation.</li><li><b>Redirect URL:</b><input type='text' value='$redirectUrlNoCode' readonly></li></ul><br><br>
        Click Submit, and then copy and paste the Client ID and Client Secret below. (Keep these values secret.) <b>After saving these settings, return to this page - setup is not complete.</b>";
      } else if($tokens == null) {
        
        $oauthUrl = $oauth->createAuthorizeUrl('merchant');
        return $output . "<b>No merchant account connected.</b><br \>To start accepting payments, you need to connect a merchant account.<br><br>
        Valid Client ID and Client Secret entered. <a href='$oauthUrl'>Click here to connect a merchant account.</a>";
      } else {
        return $output . "<b>Account connected. You're ready to accept payments.</b><br><a href='$disconnectUrl'>Disconnect account</a>";
      }
    }
}
