<?php

class Coinbase_Coinbase_RedirectController extends Mage_Core_Controller_Front_Action
{        

    public function successAction() {

        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }

}