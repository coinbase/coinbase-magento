<?php
/**
 * Attribution Notice: Based on Mage_Paypal_Model_Ipn, Mage_Paypal_IpnController
 *
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
require_once(Mage::getModuleDir('vendor', 'Coinbase_Coinbase') . "/vendor/autoload.php");

class Coinbase_Coinbase_CoinbaseController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Send expire header to ajax response
     *
     */
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with Coinbase order transaction information
     *
     * @return Coinbase_Coinbase_Model_PaymentMethod
     */
    public function getCoinbase()
    {
        return Mage::getSingleton('Coinbase_Coinbase_Model_PaymentMethod');
    }

    /**
     * When a customer chooses Bitcoin on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setCoinbaseQuoteId($session->getQuoteId());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
        $this->_redirectUrl($this->getCoinbase()->getCheckoutUrl());
    }

    /**
     * When a customer cancels payment from Coinbase
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getCoinbaseQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
            Mage::helper('coinbase_coinbase/checkout')->restoreQuote();
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Back from Coinbase
     *
     * Payment is *not* confirmed yet
     *
     */
    public function  successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getCoinbaseQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
}
