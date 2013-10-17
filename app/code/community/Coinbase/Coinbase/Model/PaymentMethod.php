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
      $coinbase = new Coinbase('fb9c14477034b3b3f979d91ddc988cdd6ad71fe56b64cd6426cdbc0e012d8559');

      $order = $payment->getOrder();
      $currency = $order->getBaseCurrencyCode();

      $callbackSecret = Mage::getStoreConfig('payment/Coinbase/callback_secret');
      if($callbackSecret == "generate") {
        // Not completely secure, but technically this secure parameter
        // is not even required because we verify the transaction in the callback.
        // This is "just in case"
        $callbackSecret = md5('secret_' . mt_rand());
        Mage::getModel('core/config')->saveConfig('payment/Coinbase/callback_secret', $callbackSecret);
      }
      
      $code = $coinbase->createButton("Order #" . $order['increment_id'], $amount, $currency, $order->getId(), array(
        'description' => 'Order #' . $order['increment_id'],
        'callback_url' => Mage::getUrl('coinbase_coinbase'). 'callback/callback/?secret=' . $callbackSecret,
        'success_url' => Mage::getUrl('coinbase_coinbase'). 'redirect/success/',
        'cancel_url' => Mage::getUrl('coinbase_coinbase'). 'redirect/cancel/',
      ))->button->code;
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