<?php

class Trollweb_Mcash_Model_Api extends Varien_Object
{

    protected $_client;

    /**
        Check if merchant exists
    */
    public function merchantExists()
    {
        return $this->getClient()->setUrl('merchant/' . $this->getMerchantId() . '/')->Get();
    }

    public function posExists()
    {
        return $this->getClient()->setUrl('pos/' . $this->getPosId().'/')->Get();
    }

    public function createPos($posId,$name)
    {
        $data = array(
            'id'        => $posId,
            'name'      => $name,
            'type'      => 'webshop',
        );

        return $this->getClient()->setUrl('pos/')->Post($data);
    }

    public function getQrImage($shortLink)
    {
        return $this->getClient()->getQrImageUrl($shortLink);
        //return 'http://chart.apis.google.com/chart?cht=qr&chs=200x200&chl=http%3A//mca.sh/s/'.$shortLink.'/';
    }

    public function getShortLink($quoteId)
    {

        $data = array(
            'callback_uri' => Mage::getUrl('mcash/callback/shortlink/id/'.$quoteId, array('_secure' => true,'_nosid'=>true)),
        );

        if ($this->getClient()->setUrl('shortlink/')->Post($data))
        {
            return $this->getClient()->getData('id');
        }
        return false;
    }

    public function paymentRequest($customer, $amount, $orderId, $text, $currency='NOK')
    {
        $data = array(
            'customer' => $customer,
            'currency' => $currency,
            'amount' => $amount,
            'allow_credit' => true,
            'pos_id' => $this->getPosId(),
            'pos_tid' => $orderId,
            'action' => 'sale',
            'text' => $text,
            'expires_in' => 120,
        );

        if ($this->getClient()->setUrl('payment_request/')->Post($data))
        {
            $responseData = $this->getClient()->getData();
            return $responseData["id"];
        }

        return false;
    }

    public function paymentRequestOutcome($transactionId)
    {
        if ($this->getClient()->setUrl('payment_request/' . $transactionId . '/outcome/')->Get())
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

    public function getUserId()
    {
        if ($this->getData('user_id')) {
            return $this->getData('user_id');
        }
        return Mage::helper('mcash')->getConfig('user_id');
    }

    public function getUserPrivKey()
    {
        if ($this->getData('user_priv_key')) {
            return $this->getData('user_priv_key');
        }
        return Mage::helper('mcash')->getConfig('user_priv_key');
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

    protected function getClient()
    {
        if (!isset($this->_client)) {
            $this->_client = Mage::getModel('mcash/api_client');
            $this->_client->setUserId($this->getUserId());
            $this->_client->setUserPrivateKey($this->getUserPrivKey());
            $this->_client->setMerchantId($this->getMerchantId());
            if (Mage::helper('mcash')->getConfig('test')) {
                $this->_client->setTest();
            }
        }
        return $this->_client;
    }

}
