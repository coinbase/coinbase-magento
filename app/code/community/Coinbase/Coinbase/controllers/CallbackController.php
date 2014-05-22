<?php

class Coinbase_Coinbase_CallbackController extends Mage_Core_Controller_Front_Action
{        

    public function callbackAction() {

      require_once(Mage::getModuleDir('coinbase-php', 'Coinbase_Coinbase') . "/coinbase-php/Coinbase.php");
      
      $secret = $_REQUEST['secret'];
      $postBody = json_decode(file_get_contents('php://input'));
      $correctSecret = Mage::getStoreConfig('payment/Coinbase/callback_secret');

      // To verify this callback is legitimate, we will:
      //   a) check with Coinbase the submitted order information is correct.
      $apiKey = Mage::getStoreConfig('payment/Coinbase/api_key');
      $apiSecret = Mage::getStoreConfig('payment/Coinbase/api_secret');
      $coinbase = Coinbase::withApiKey($apiKey, $apiSecret);
      $cbOrderId = $postBody->order->id;
      $orderInfo = $coinbase->getOrder($cbOrderId);
      if(!$orderInfo) {
        Mage::log("Coinbase: incorrect callback with incorrect Coinbase order ID $cbOrderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }
      
      //   b) using the verified order information, check which order the transaction was for using the custom param.
      $orderId = $orderInfo->custom;
      $order = Mage::getModel('sales/order')->load($orderId);
      if(!$order) {
        Mage::log("Coinbase: incorrect callback with incorrect order ID $orderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }
      
      //   c) check the secret URL parameter.
      if($secret !== $correctSecret) {
        Mage::log("Coinbase: incorrect callback with incorrect secret parameter $secret.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      // The callback is legitimate. Update the order's status in the database.
      $payment = $order->getPayment();
      $payment->setTransactionId($cbOrderId)
        ->setPreparedMessage("Paid with Coinbase order $cbOrderId.")
        ->setShouldCloseParentTransaction(true)
        ->setIsTransactionClosed(0);
        
      if("completed" == $orderInfo->status) {
        $payment->registerCaptureNotification($orderInfo->total_native->cents / 100);
      } else {
        $cancelReason = $postBody->cancellation_reason;
        $order->registerCancellation("Coinbase order $cbOrderId cancelled: $cancelReason");
      }

      Mage::dispatchEvent('coinbase_callback_received', array('status' => $orderInfo->status, 'order_id' => $orderId));
      $order->save();
    }

}
