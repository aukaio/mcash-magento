<?php

class Trollweb_Mcash_Model_Api_Client
{

    const API_HOST_TEST = "playgroundmcashservice.appspot.com";
    const API_HOST = "api.mca.sh";
    const API_VERSION = "1";

    const MCASH_PUB_CERT = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8Pg5kMWZzX0U+ZGts6Ws
oLrI1bN5PjXzFRAPza19qYrONVxhFlJx8AQWohISL1hKVPJCMuyQKhs0/2jtWk+E
mDHXFafW+kYV7lseznj6nW49VFyxHYdQDNHgpyUA5p+lmZABbmcKGabw/Cp28vtH
im4zWBGVXnQ7UPm1peMzeuaB7L246J+ZcfLpd3trSWg2mywB23rqELzTNKi0s7cb
kS+2gk5B72q3qcaTO47rPgEVcUTB2A+jxcu6rOVFCbhQ8+JkLDPeHPDuIBQ5mAwN
XLY+3ffovc31S5cMhquiKaYYwiuxeI23AWtNV2FoD00bm4q+5XCuBGgPJf3nkNYV
eQIDAQAB
-----END PUBLIC KEY-----';

    protected $_client;
    protected $_secret = '';
    protected $_data;
    protected $_test = false;
    protected $_response = false;
    protected $_errorMessage = false;
    protected $_requestMethod = '';
    protected $_url = '';
    protected $_config;

    public function __construct()
    {
        $this->_config = array(
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
        );
        
    }

    public function setUrl($url)
    {
        $this->_url = $this->getBaseUrl().$url;
        return $this;
    }


    public function getQrImageUrl($shortlinkId,$args='')
    {
	return 'https://'.($this->_test ? self::API_HOST_TEST : self::API_HOST).'/shortlink/v'.self::API_VERSION.'/qr_image/'.$shortlinkId.'/'.$args;
    }

    public function setSecret($secret)
    {
        $this->_secret = $secret;
        return $this;
    }

    public function setTest($test=true)
    {
        $this->_test = $test;
        return $this;
    }

    public function getData($key=null)
    {
        if ($key) {
            if (isset($this->_data[$key])) {
                return $this->_data[$key];
            }
            else {
                return false;
            }
        }
        return $this->_data;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }


    public function Post($data) {
        $this->_requestMethod = Zend_Http_Client::POST;
        $raw_data = Mage::helper('core')->jsonEncode($data);
        $signature = $this->signRequest(Zend_Http_Client::POST,$raw_data);
        $_client = $this->getClient($signature);
        $_client->setRawData($raw_data);

        $this->_response = $_client->request(Zend_Http_Client::POST);

        return $this->checkResponse($this->_response);
    }

    public function Get() {
        $this->_requestMethod = Zend_Http_Client::GET;
        $signature = $this->signRequest(Zend_Http_Client::GET);
        $_client = $this->getClient($signature);

        $this->_response = $_client->request(Zend_Http_Client::GET);
        return $this->checkResponse($this->_response);
    }

    public function Put($data)
    {
        $this->_requestMethod = Zend_Http_Client::PUT;
        $raw_data = Mage::helper('core')->jsonEncode($data);
        $signature = $this->signRequest(Zend_Http_Client::PUT,$raw_data);
        $_client = $this->getClient($signature);
        $_client->setRawData($raw_data);

        $this->_response = $_client->request(Zend_Http_Client::PUT);
        return $this->checkResponse($this->_response);
    }


    protected function checkResponse($response)
    {
        $this->_data = false;

        $contentMd5 = $response->getHeader('Content-md5');
        $signature = $response->getHeader('X-mcash-signature');
        $signatureData = $this->_buildSignatureData($this->_requestMethod, $this->_url, $contentMd5);
        $validSignature = $this->verifySignature($signatureData, $signature);

        if (!$validSignature) {
            Mage::log("[mCASH] Invalid signature!");
            $_errorMessage = "Signature in response did not pass validation";
            return false;
        }

        if ($response->isSuccessful()) {
            $this->_data = Mage::helper('core')->jsonDecode($response->getBody());
            return true;
        }
        else {
            try {
                $error = Mage::helper('core')->jsonDecode($response->getBody());
            }
            catch (Exception $e) {
                $error = array('error' => 'Unknown error');
                Mage::log('[mCASH] Calling URL '.$this->_url."\n"."No valid response (".$response->getStatus().'): '.$response->getBody(),Zend_Log::ERR);
            }
            $errorMessage = 'API returned '.$response->getStatus();
            if (isset($error['error'])) {
                $errorMessage = $error['error'];
            }
            $this->_errorMessage = $errorMessage;
	    Mage::log('[mCASH] '.$errorMessage,Zend_Log::ERR);
            return false;
        }
    }

    public function verifySignature($data, $signature)
    {
        return openssl_verify($data, base64_decode($signature), self::MCASH_PUB_CERT);
    }    

    public function buildSignatureData($method, $url, $data) {
        $contentMd5 = $data ? base64_encode(md5($data, true)) : "";
        return $this->_buildSignatureData($method, $url, $contentMd5);
    }

    // Signature data has the following pattern: <request_method>|<request_url>|<content-md5>
    private function _buildSignatureData($method, $url, $contentMd5) {
        return sprintf("%s|%s|%s", strtoupper($method), $url, $contentMd5);
    }
    
    protected function getBaseUrl()
    {
        return 'https://'.($this->_test ? self::API_HOST_TEST : self::API_HOST).'/merchantapi/v'.self::API_VERSION.'/';
    }

    protected function signRequest($method,$data='')
    {
        $requestData = $method.$this->_url.$data;
        return base64_encode(hash_hmac('sha256',$requestData,$this->_secret,true));
    }

    protected function getClient($signature)
    {
        $_client = new Zend_Http_Client($this->_url, $this->_config);
        $_client->setHeaders('Accept','application/json,application/vnd.mcash-merchantapi-v'.self::API_VERSION.'+json'); 
        $_client->setHeaders('Content-type','application/json');
        $_client->setHeaders('X-Mcash-Signature-Method','HMAC-SHA256');
        $_client->setHeaders('X-Mcash-Signature',$signature);

        return $_client;        
    }

}
