<?php
/**
 * Attribution Notice: Based on Mage_Paypal_Model_Ipn, Mage_Paypal_IpnController
 *
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
require_once(Mage::getModuleDir('vendor', 'Coinbase_Coinbase') . "/vendor/autoload.php");

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Enum\NotificationType;

class Coinbase_Coinbase_CallbackController extends Mage_Core_Controller_Front_Action
{
    protected $_client         = null;
    protected $_notification   = null;
    protected $_order          = null;
    protected $_coinbase_order = null;

    public function _construct()
    {
        $configuration = Configuration::apiKey('', '');
        $this->_client = Client::create($configuration);
    }

    protected function _registerMispayment()
    {
        $coinbase_order_code = $this->_coinbase_order->getCode();
        $this->_order->hold()->save();
        $this->_order->addStatusHistoryComment("Coinbase order $coinbase_order_code mispaid; manual intervention required")
            ->setIsCustomerNotified(false)
            ->save();

        Mage::log("Coinbase: processed mispayment", null, 'payment_coinbase.log');
    }

    protected function _registerPaymentCapture()
    {
        $coinbase_order_code = $this->_coinbase_order->getCode();

        $payment = $this->_order->getPayment();

        $payment->setTransactionId($coinbase_order_code)
            ->setCurrencyCode($this->_coinbase_order->getAmount()->getCurrency())
            ->setPreparedMessage("Paid via Coinbase")
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification(
                $this->_coinbase_order->getAmount()->getAmount(),
                true // No fraud detection required with bitcoin :)
            );

        $this->_order->save();

        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$this->_order->getEmailSent()) {
            $this->_order->queueNewOrderEmail()->addStatusHistoryComment(
                "Notified customer about invoice $invoice->getIncrementId()"
            )
            ->setIsCustomerNotified(true)
            ->save();
        }

        Mage::log("Coinbase: processed successful payment", null, 'payment_coinbase.log');
    }

    protected function _verifyCallbackAuthenticity()
    {
        $raw_post_body = $this->getRequest()->getRawBody();
        $signature = $this->getRequest()->getHeader('CB-SIGNATURE');
        $authentic = $this->_client->verifyCallback($raw_post_body, $signature);

        if (!$authentic) {
            throw new Exception('Callback authenticity could not be verified.');
        }

        $this->_notification = $this->_client->parseNotification($raw_post_body);
    }

    protected function _handleUnknownCallback()
    {
        Mage::log("Coinbase: received unknown callback", null, 'payment_coinbase.log');
    }

    protected function _handlePingCallback()
    {
        Mage::log("Coinbase: handled ping callback", null, 'payment_coinbase.log');
    }

    protected function _loadOrder()
    {
        $order_id     = $this->_coinbase_order->getMetadata()['order_id'];
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        if (!$this->_order && $this->_order->getId()) {
            throw new Exception('Could not find Magento order with id $order_id');
        }

        $callback_replay_token = $this->_coinbase_order->getMetadata()['replay_token'];
        $replay_token = $this->_order->getPayment()->getAdditionalInformation('replay_token');

        if ($replay_token !== $callback_replay_token) {
            throw new Exception('Replay tokens did not match');
        }
    }

    public function callbackAction()
    {
        Mage::log("Coinbase: received callback", null, 'payment_coinbase.log');

        try
        {
            // Cryptographically verify authenticity of callback
            $this->_verifyCallbackAuthenticity();

            switch ($this->_notification->getType()) {
                case NotificationType::PING:
                    $this->_handlePingCallback();
                    break;
                case NotificationType::ORDER_PAID:
                case NotificationType::ORDER_MISPAID:
                    $this->_coinbase_order = $this->_notification->getData();
                    $this->_loadOrder();
                    break;
                default:
                    $this->_handleUnknownCallback();
                break;
            }

            switch ($this->_notification->getType()) {
                case NotificationType::ORDER_PAID:
                    $this->_registerPaymentCapture();
                    break;
                case NotificationType::ORDER_MISPAID:
                    $this->_registerMispayment();
                    break;
            }

            return $this->_success();

        } catch (\Exception $e) {
            Mage::log("Coinbase: ipn failure (details below)", null, 'payment_coinbase.log');
            Mage::log($e->getMessage(), null, 'payment_coinbase.log');
            return $this->_failure();
        }
    }

    protected function _success()
    {
        Mage::app()->getResponse()->setHttpResponseCode(200);
    }

    protected function _failure()
    {
        Mage::app()->getResponse()->setHttpResponseCode(400);
    }
}
