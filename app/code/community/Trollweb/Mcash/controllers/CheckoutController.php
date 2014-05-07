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

    public function qrAction() {
        $api = Mage::getModel('mcash/api');
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if (!$quote) {
            $this->getResponse()->setHttpResponseCode(400);
            return false;
        }

        $payment = $quote->getPayment();
        if (!$payment) {
            $this->getResponse()->setHttpResponseCode(400);
            return false;
        }

        if (!$payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK)) {
            if ($shortLink = $api->getShortLink($quote->getId())) {
                $payment->setAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK,$shortLink)->save();
            }
        }

        $url = $api->getQrImage($payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK));

        $jsonData = json_encode(array(
            "url" => $url,
        ));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
