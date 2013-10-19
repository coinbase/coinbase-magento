<?php

class Coinbase_Coinbase_RedirectController extends Mage_Core_Controller_Front_Action
{        

    public function successAction() {

        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
    
    public function cancelAction() {
    
      $orderId = $_GET['order']['custom'];
      $order = Mage::getModel('sales/order')->load($orderId);
      
      
      if(!$order->isPaymentReview() || $order->hasInvoices()) {
        $msg = "Your order could not be cancelled. Please contact customer support concerning Order ID $orderId.";
      } else {
        
        $msg = "Your order has been cancelled.";
        $order->registerCancellation("Order was cancelled during checkout.")->save();
      }
      
      Mage::getSingleton('core/session')->addError($msg);
      $this->_redirectUrl(Mage::getBaseUrl());
    }
    
    public function oauthAction() {

        $redirectUrlNoCode = Mage::getUrl("adminhtml/coinbaseoauth/redirect");
        $this->_redirectUrl($redirectUrlNoCode . "key/$_GET[key]/?code=$_GET[code]&key=$_GET[key]");
    }

}