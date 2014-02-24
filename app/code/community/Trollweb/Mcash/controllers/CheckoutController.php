<?php

class Trollweb_Mcash_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function statusAction() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $token = $quote->getPayment()->getAdditionalInformation('mcash_token');

        $jsonData = json_encode(array(
            "scanned" => !empty($token),
        ));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
