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
       if (!$this->getSession()->getMcashShortLink()) {
            $quote = $this->getSession()->getQuote();
            if (!$quote) {
                return false;
            }
            if ($shortLink = $api->getShortLink($quote->getId())) {
                $this->getSession()->setMcashShortLink($shortLink);   
            }
         
       }
       return $api->getQrImage($this->getSession()->getMcashShortLink());
    }

    protected function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}