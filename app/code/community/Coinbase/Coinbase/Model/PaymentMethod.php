<?php
 
class Coinbase_Coinbase_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
{
    protected $_code = 'coinbase';
 
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
    protected $_canCapture              = true;
 
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
 
    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     */
}
?>