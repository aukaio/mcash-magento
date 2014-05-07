<?php

class Trollweb_Mcash_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('mcash/form.phtml');
        parent::_construct();
    }

    public function getQrImageApiUrl() {
        return Mage::getUrl('mcash/checkout/qr', array("_secure" => true));
    }

    public function isScanned() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $token = $quote->getPayment()->getAdditionalInformation('mcash_token');
        return !empty($token);
    }

    public function getLogoUrl() {
        return $this->getSkinUrl('mcash/mcash.png', array('_secure' => true));
    }

    protected function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
