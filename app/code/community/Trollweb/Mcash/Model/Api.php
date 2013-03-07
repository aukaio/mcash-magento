<?php

class Trollweb_Mcash_Model_Api extends Varien_Object
{

    protected $_client;

    /**
        Check if merchant exists
    */
    public function merchantExists()
    {
        return $this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/')->Get();
    }

    public function posExists()
    {
        return $this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/pos/'.$this->getPosId().'/')->Get();    
    }

    public function createPos($posId,$name)
    {
        $data = array(
            'name'      => $name,
            'type'      => 'webshop',
            'secret'    => $this->getPostSecret(),
                    );

        return $this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/pos/'.$posId.'/')->Put($data);
    }

    public function getQRImage($shortLink)
    {

        return 'http://chart.apis.google.com/chart?cht=qr&chs=200x200&chl=http%3A//mca.sh/s/'.$shortLink.'/';
    }

    public function getShortLink($quoteId)
    {

        $data = array(
            'callUri' => Mage::getUrl('mcash/callback/shortlink/id/'.$quoteId, array('_secure' => true)),
            );

        if ($this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/shortlink/')->Post($data))
        {
            return $this->getClient()->getData('id');
        }
        return false;
    }
    
    /* Set / Get */

    public function getErrorMessage()
    {
        if (!$this->getData('error_message')) {
            if ($this->getClient()->getErrorMessage()) {
                return $this->getClient()->getErrorMessage();
            }
        }
        return $this->getData('error_message');
    }

    public function getMerchantId()
    {
        if ($this->getData('merchant_id')) {
            return $this->getData('merchant_id');
        }
        return Mage::helper('mcash')->getConfig('merchant_id');        
    }

    public function getPosId()
    {
        if ($this->getData('pos_id')) {
            return $this->getData('pos_id');
        }
        return Mage::helper('mcash')->getConfig('pos_id');
    }


    protected function getSecret()
    {
        if ($this->getData('secret')) {
            return $this->getData('secret');
        }
        return Mage::helper('mcash')->getConfig('secret');
    }


    protected function getPosSecret()
    {
        return $this->getSecret();
    }

    /** Protected **/ 

    protected function getClient()
    {
        if (!isset($this->_client)) {
            $this->_client = Mage::getModel('mcash/api_client');
            $this->_client->setSecret($this->getSecret());
            if (Mage::helper('mcash')->getConfig('test')) {
                $this->_client->setTest();
            }
        }
        return $this->_client;
    }
    
}