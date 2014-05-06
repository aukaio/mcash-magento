<?php

class Trollweb_Mcash_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('mcash/form.phtml');
        parent::_construct();
    }

    public function getQRImage()
    {
        $api = Mage::getModel('mcash/api'); 
        $quote = $this->getSession()->getQuote();
        if (!$quote) {
            return false;
        }
        $payment = $quote->getPayment();
        if (!$payment) {
            return false;
        }
        if (!$payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK)) {
            if ($shortLink = $api->getShortLink($quote->getId())) {
                $payment->setAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK,$shortLink)->save();
            }
         
       }
       return $api->getQrImage($payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK));
    }

    public function isScanned() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $token = $quote->getPayment()->getAdditionalInformation('mcash_token');
        return !empty($token);
    }

    protected function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
