<?php

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;

class Coinbase_Coinbase_CallbackController extends Mage_Core_Controller_Front_Action
{
    public function callbackAction() {
      require_once(Mage::getModuleDir('vendor', 'Coinbase_Coinbase') . "/vendor/autoload.php");

      $configuration = Configuration::apiKey('', '');
      $client = Client::create($configuration);

      // Cryptographically verify authenticity of callback
      $raw_post_body = file_get_contents('php://input');
      $signature = $_SERVER['HTTP_X_SIGNATURE'];
      $authentic = $client->verifyCallback($raw_post_body, $signature);

      if (!$authentic) {
        Mage::log("Coinbase: incorrect callback with incorrect signature.");
        header("HTTP/1.1 400 Bad Request");
        return;
      }

      $postBody = json_decode($raw_post_body);
      if (isset($postBody->order)) {
        $orderInfo = $postBody->order;
        $orderId   = $orderInfo->metadata->order_id;
      } else if (isset($post_body->payout)) {
        Mage::log("Coinbase: ignoring payout callback");
        header("HTTP/1.1 200 OK");
        return;
      } else {
        Mage::log("Coinbase: unrecognized callback");
        header("HTTP/1.1 400 Bad Request");
        return;
      }

      $order = Mage::getModel('sales/order')->load($orderId);
      if(!$order) {
        Mage::log("Coinbase: incorrect callback with incorrect order ID $orderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      $payment = $order->getPayment();
      $payment->setTransactionId($orderInfo->id)
        ->setPreparedMessage("Paid with Coinbase order $orderInfo->id.")
        ->setShouldCloseParentTransaction(true)
        ->setIsTransactionClosed(0);

      if("completed" == $coinbaseOrder->status) {
        $payment->registerCaptureNotification($orderInfo->total_native->cents / 100);
      } else {
        $cancelReason = $postBody->cancellation_reason;
        $order->registerCancellation("Coinbase order $orderInfo->id cancelled: $cancelReason");
      }

      Mage::dispatchEvent('coinbase_callback_received', array('status' => $orderInfo->status, 'order_id' => $orderId));
      $order->save();
    }

}
