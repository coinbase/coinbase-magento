<?php

require_once(Mage::getModuleDir('vendor', 'Coinbase_Coinbase') . "/vendor/autoload.php");

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Checkout;
use Coinbase\Wallet\Value\Money;

class Coinbase_Coinbase_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'Coinbase';
    protected $_isInitializeNeeded      = true;

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getCheckoutUrl()
    {
        $apiKey = Mage::getStoreConfig('payment/Coinbase/api_key');
        $apiSecret = Mage::getStoreConfig('payment/Coinbase/api_secret');
        if($apiKey == null || $apiSecret == null) {
            throw new Exception("Before using the Coinbase plugin, you need to enter an API Key and Secret in Magento Admin > Configuration > System > Payment Methods > Coinbase.");
        }
        $configuration = Configuration::apiKey($apiKey, $apiSecret);
        $coinbase = Client::create($configuration);

        $orderId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        // Protect against callback replay attacks
        $replayToken = bin2hex(openssl_random_pseudo_bytes(16));
        $payment = $order->getPayment();
        $payment->setAdditionalInformation("replay_token", $replayToken)->save();

        $params = array(
            'amount' => new Money(
                $order->getTotalDue(),
                $order->getBaseCurrencyCode()
            ),
            'name'              => "Order #" . $orderId,
            'description'       => 'Order #' . $orderId,
            'metadata'          => array(
                'order_id' => $orderId,
                'replay_token' => $replayToken
            ),
            'notifications_url' => Mage::getUrl('coinbase_coinbase/callback/callback'),
            'success_url'       => Mage::getUrl('coinbase_coinbase/coinbase/success')
        );

        try {
            $checkout = new Checkout($params);
            $coinbase->createCheckout($checkout);
            $code = $checkout->getEmbedCode();
        } catch (Exception $e) {
            $message = print_r($e, true);
            Mage::log("Coinbase: Error generating checkout code $message", null, 'payment_coinbase.log');
            Mage::throwException("There was a problem with your payment. Please select another payment method and try again.");
        }

        return 'https://www.coinbase.com/checkouts/' . $code;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('coinbase_coinbase/coinbase/redirect');
    }
}
