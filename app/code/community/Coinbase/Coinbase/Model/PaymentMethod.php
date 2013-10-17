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
    
      // Step 1: Use the Coinbase API to create redirect URL.
      $redirectUrl = 'https://coinbase.com/checkouts/4d4b84bbad4508b64b61d372ea394dad';
    
      // Step 2: Redirect customer to payment page
      $payment->setIsTransactionPending(true); // Set status to Payment Review
      Mage::getSingleton('customer/session')->setRedirectUrl($redirectUrl);
      
      return $this;
    }
    
    public function getOrderPlaceRedirectUrl()
    {
      return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
}
?>