<?php

class Coinbase_Coinbase_RedirectController extends Mage_Core_Controller_Front_Action
{        

    public function successAction() {

        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
    
    public function oauthAction() {

        $redirectUrlNoCode = Mage::getUrl("adminhtml/coinbaseoauth/redirect");
        $this->_redirectUrl($redirectUrlNoCode . "key/$_GET[key]/?code=$_GET[code]&key=$_GET[key]");
    }

}