<?php
 
class Coinbase_Coinbase_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'Coinbase';
 
    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;
 
    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;
 
    /**
     * Can capture funds online?
     */
    protected $_canCapture              = false;
 
    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;
 
    /**
     * Can refund online?
     */
    protected $_canRefund               = false;
 
    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = false;
 
    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;
 
    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;
 
    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;
  
  
    public function authorize(Varien_Object $payment, $amount) 
    {

      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");

      // Step 1: Use the Coinbase API to create redirect URL.
      $clientId = Mage::getStoreConfig('payment/Coinbase/oauth_clientid');
      $clientSecret = Mage::getStoreConfig('payment/Coinbase/oauth_clientsecret');
      $redirectUrl = Mage::getUrl("coinbase_coinbase/redirect/oauth");
      $oauth = new Coinbase_Oauth($clientId, $clientSecret, $redirectUrl);
      $tokens = unserialize(Mage::getStoreConfig('payment/Coinbase/oauth_tokens'));

      if($tokens == null) {
        throw new Exception("Before using the Coinbase plugin, you need to connect a merchant account in Magento Admin > Configuration > System > Payment Methods > Coinbase.");
      }

      $coinbase = new Coinbase($oauth, $tokens);

      $order = $payment->getOrder();
      $currency = $order->getBaseCurrencyCode();

      $callbackSecret = Mage::getStoreConfig('payment/Coinbase/callback_secret');
      if($callbackSecret == "generate") {
        // Important to keep the callback URL a secret
        $callbackSecret = md5('secret_' . mt_rand());
        Mage::getModel('core/config')->saveConfig('payment/Coinbase/callback_secret', $callbackSecret)->cleanCache();
        Mage::app()->getStore()->resetConfig();
      }
      
      $name = "Order #" . $order['increment_id'];
      $custom = $order->getId();
      $params = array(
            'description' => 'Order #' . $order['increment_id'],
            'callback_url' => Mage::getUrl('coinbase_coinbase'). 'callback/callback/?secret=' . $callbackSecret,
            'success_url' => Mage::getUrl('coinbase_coinbase'). 'redirect/success/',
            'cancel_url' => Mage::getUrl('coinbase_coinbase'). 'redirect/cancel/',
          );
      
      // Generate the code
      try {
        $code = $coinbase->createButton($name, $amount, $currency, $custom, $params)->button->code;
      } catch (Coinbase_TokensExpiredException $e) {
        // Refresh tokens
        try {
          $tokens = $oauth->refreshTokens($tokens);
        } catch (Exception $f) {
          // Give up. 
          $this->tokenFail($tokens, "Could not refresh tokens.");
        }
        // Save new tokens
        Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', serialize($tokens));
        $coinbase = new Coinbase($oauth, $tokens);
        // And try again...
        try {
          $code = $coinbase->createButton($name, $amount, $currency, $custom, $params)->button->code;
        } catch (Coinbase_TokensExpiredException $e) {
          // Give up. 
          $this->tokenFail($tokens, "Could not create button after refreshing tokens.");
        }
      }
      $redirectUrl = 'https://coinbase.com/checkouts/' . $code;
    
      // Step 2: Redirect customer to payment page
      $payment->setIsTransactionPending(true); // Set status to Payment Review while waiting for Coinbase postback
      Mage::getSingleton('customer/session')->setRedirectUrl($redirectUrl);
      
      return $this;
    }
    
    function tokenFail($tokens, $msg) {
    
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientid', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_clientsecret', null);
      Mage::getModel('core/config')->saveConfig('payment/Coinbase/oauth_tokens', null)->cleanCache();
      Mage::app()->getStore()->resetConfig();
      throw new Exception("No account is connected, or the current account is not working. You need to connect a merchant account in Magento Admin > Configuration > System > Payment Methods > Coinbase. ($msg / $tokens)");
   }
    
    public function getOrderPlaceRedirectUrl()
    {
      return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
}
?>