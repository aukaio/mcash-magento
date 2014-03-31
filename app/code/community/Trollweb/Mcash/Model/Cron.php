<?php

class Trollweb_Mcash_Model_Cron extends Mage_Core_Model_Abstract {

    public function reauthorize($schedule) {
        Mage::log("Reauthorizing...");

        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('status', array('nin' => array("complete", "canceled")));

        Mage::getSingleton('core/resource_iterator')->walk($orders->getSelect(), array(array($this, 'reauthorizeOrder')));
    }

    public function reauthorizeOrder($data) {
        $id = $data["row"]["entity_id"];
        $order = Mage::getModel('sales/order')->load($id);

        $payment = $order->getPayment();
        $method = $payment->getMethod();


        if (stristr($method, "mcash") === FALSE) {
            return;
        }

        // Grab last auth transaction on this order
        $transaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $id))
            ->addAttributeToFilter('txn_type', array('eq' => 'authorization'))
            ->getLastItem();

        $transactionId = $transaction->getTxnId();

        if (!$transactionId) {
            return;
        }

        $api = Mage::getModel('mcash/api');

        Mage::log("payment request outcome:");
        Mage::log($api->paymentRequestOutcome($transactionId));

        Mage::log(sprintf("Reauthorizing: %s: %s [%s] (%s)\n", $order->getIncrementId(), $method, $transactionId, $order->getStatus()));
        $api->paymentRequestReauthorize($transactionId);
    }
}
