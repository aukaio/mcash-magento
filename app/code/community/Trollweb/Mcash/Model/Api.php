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

    public function getQrImage($shortLink)
    {
        return $this->getClient()->getQrImageUrl($shortLink);
        //return 'http://chart.apis.google.com/chart?cht=qr&chs=200x200&chl=http%3A//mca.sh/s/'.$shortLink.'/';
    }

    public function getShortLink($quoteId)
    {

        $data = array(
            'callUri' => Mage::getUrl('mcash/callback/shortlink/id/'.$quoteId, array('_secure' => true,'_nosid'=>true)),
            );

        if ($this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/shortlink/')->Post($data))
        {
            return $this->getClient()->getData('id');
        }
        return false;
    }

    public function sale($customer,$amount,$orderId,$text,$currency='NOK')
    {
        $data = array(
            'posTimestamp'     => date('Y-m-d h:i:s'),
            'expiration'    => 120,
            'customer'      => $customer,
            'amount'        => $amount,
            'currency'      => $currency,
            'allowCredit'   => true,
            'text'          => $text,
//            'callbackUri'   => Mage::getUrl('mcash/callback/order/id/'.$orderId, array('_secure' => true,'_nosid'=>true)),
        );

        if ($this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/pos/'.$this->getPosId().'/sale_request/'.$orderId.'/')->Put($data))
        {
            return true;
        }
        return false;


    }

    public function saleOutcome($orderId)
    {
        if ($this->getClient()->setUrl('merchant/'.$this->getMerchantId().'/pos/'.$this->getPosId().'/sale_request/'.$orderId.'/outcome/')->Get())
        {
            return $this->getClient()->getData();
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

    public function getResponseData($key)
    {
        if ($this->getClient()->getData($key)) {
             return $this->getClient()->getData($key);
        }
        return false;
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
