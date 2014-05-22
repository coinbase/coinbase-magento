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
      $apiKey = Mage::getStoreConfig('payment/Coinbase/api_key');
      $apiSecret = Mage::getStoreConfig('payment/Coinbase/api_secret');

      if($apiKey == null || $apiSecret == null) {
        throw new Exception("Before using the Coinbase plugin, you need to enter an API Key and Secret in Magento Admin > Configuration > System > Payment Methods > Coinbase.");
      }

      $coinbase = Coinbase::withApiKey($apiKey, $apiSecret);

      $order = $payment->getOrder();
      $currency = $order->getBaseCurrencyCode();

      $callbackSecret = Mage::getStoreConfig('payment/Coinbase/callback_secret');
      if($callbackSecret == "generate") {
        // Important to keep the callback URL a secret
        $callbackSecret = md5('secret_' . mt_rand());
        Mage::getModel('core/config')->saveConfig('payment/Coinbase/callback_secret', $callbackSecret)->cleanCache();
        Mage::app()->getStore()->resetConfig();
      }
      
      $successUrl = Mage::getStoreConfig('payment/Coinbase/custom_success_url');
      $cancelUrl = Mage::getStoreConfig('payment/Coinbase/custom_cancel_url');
      if ($successUrl == false) {
        $successUrl = Mage::getUrl('coinbase_coinbase'). 'redirect/success/';
      }
      if ($cancelUrl == false) {
        $cancelUrl = Mage::getUrl('coinbase_coinbase'). 'redirect/cancel/';
      }

      $name = "Order #" . $order['increment_id'];
      $custom = $order->getId();
      $params = array(
            'description' => 'Order #' . $order['increment_id'],
            'callback_url' => Mage::getUrl('coinbase_coinbase'). 'callback/callback/?secret=' . $callbackSecret,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'info_url' => Mage::getBaseUrl()
          );

      // Generate the code
      try {
        $code = $coinbase->createButton($name, $amount, $currency, $custom, $params)->button->code;
      } catch (Exception $e) {
        throw new Exception("Could not generate checkout page. Double check your API Key and Secret. " . $e->getMessage());
      }
      $redirectUrl = 'https://coinbase.com/checkouts/' . $code;
    
      // Step 2: Redirect customer to payment page
      $payment->setIsTransactionPending(true); // Set status to Payment Review while waiting for Coinbase postback
      Mage::getSingleton('customer/session')->setRedirectUrl($redirectUrl);
      
      return $this;
    }

    
    public function getOrderPlaceRedirectUrl()
    {
      return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
}
?>
