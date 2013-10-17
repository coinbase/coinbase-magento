<?php

class Coinbase_Coinbase_CallbackController extends Mage_Core_Controller_Front_Action
{        

    public function callbackAction() {

      $secret = $_REQUEST['secret'];
      $postBody = json_decode(file_get_contents('php://input'));
      $correctSecret = Mage::getStoreConfig('payment/Coinbase/callback_secret');

      // To verify this callback is legitimate, we will:
      //   a) check with Coinbase the submitted order information is correct.
      //   b) using the verified order information, check which order the transaction was for using the custom param.
      //   c) check the secret URL parameter.
  
      // The callback is legitimate. Update the order's status in the database.

    }

}