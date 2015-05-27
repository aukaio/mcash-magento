<?php

class Trollweb_Mcash_Model_Api_Client
{

    const API_HOST_TEST = "mcashtestbed.appspot.com";
    const API_HOST = "api.mca.sh";
    const API_VERSION = "1";

    protected $_client;
    protected $_secret = '';
    protected $_data;
    protected $_test = false;
    protected $_response = false;
    protected $_errorMessage = false;
    protected $_url = '';
    protected $_config;
    private $_headers;
    private $_userId;
    private $_userPrivKey;
    private $_merchantId;

    public function __construct()
    {
        $this->_config = array(
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
        );

    }

    public function setUrl($url)
    {
        $this->_url = $this->getBaseUrl() . $url;
        return $this;
    }


    public function getQrImageUrl($shortlinkId,$args='')
    {
        return 'https://'.($this->_test ? self::API_HOST_TEST : self::API_HOST).'/shortlink/v'.self::API_VERSION.'/qr_image/'.$shortlinkId.'/'.$args;
    }

    public function setUserId($id)
    {
        $this->_userId = $id;
        return $this;
    }

    public function setUserPrivateKey($key)
    {
        $this->_userPrivKey = $key;
        return $this;
    }

    public function setMerchantId($id)
    {
        $this->_merchantId = $id;
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

    public function Get() {
        $_client = $this->getClient();
        $this->_response = $this->sendSignedRequest($_client, "GET");
        return $this->checkResponse($this->_response);
    }

    public function Post($data) {
        $raw_data = Mage::helper('core')->jsonEncode($data);
        $_client = $this->getClient();
        $this->_response = $this->sendSignedRequest($_client, "POST", $raw_data);
        return $this->checkResponse($this->_response);
    }

    public function Put($data) {
        $raw_data = Mage::helper('core')->jsonEncode($data);
        $_client = $this->getClient();
        $this->_response = $this->sendSignedRequest($_client, "PUT", $raw_data);
        return $this->checkResponse($this->_response);
    }

    private function setRawData($client, $data) {
        $client->setRawData($data);
        $this->setHeader("X-Mcash-Content-Digest", $this->contentDigest($data));
    }

    private function sendSignedRequest($client, $method, $data="") {
        $this->setRawData($client, $data);

        // Add authorization header with signature
        $signature = $this->sign($method, $this->_url, $this->_headers);
        $this->setHeader('Authorization', "RSA-SHA256 " . $signature);

        // Set headers on http client
        foreach ($this->_headers as $key => $value) {
            $client->setHeaders($key, $value);
        }

        // Send request
        return $client->request($method);
    }

    protected function checkResponse($response)
    {
        $this->_data = false;

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
                Mage::log('mCASH Calling URL '.$this->_url."\n"."No valid response (".$response->getStatus().'): '.$response->getBody(),Zend_Log::ERR);
            }
            $errorMessage = 'API returned '.$response->getStatus();
            if (isset($error['error'])) {
                $errorMessage = $error['error'];
            }
            $this->_errorMessage = $errorMessage;
            Mage::log('mCASH '.$errorMessage,Zend_Log::ERR);
            return false;
        }
    }

    protected function getBaseUrl()
    {
        return 'https://' . ($this->_test ? self::API_HOST_TEST : self::API_HOST) . '/merchant/v' . self::API_VERSION . '/';
    }

    // The base64 encoded hash digest of the request body. If the body is
    // empty, the hash should be computed on an empty string. The value of the
    // header should be on the form <algorithm (uppercase)>=<digest value>.
    private function contentDigest($data="") {
        return "SHA256=" . base64_encode(hash("sha256", $data, true));
    }

    // The current UTC time. The time format is YYYY-MM-DD hh:mm:ss.
    private function utcTimestamp() {
        return date("Y-m-d H:i:s", time());
    }

    // The string that is to be signed (the signature message) is
    // constructed from the request in the following manner:
    //
    // <method>|<url>|<headers>
    // Here, method is the HTTP method used in the request, url is the
    // full url including protocol and query component (the part after ?)
    // but without fragment component (The part after #). The scheme name
    // (typically https) and hostname components are always lowercase, while
    // the rest of the url is case sensitive. The headers part is a
    // querystring using header names and values as key-value pairs. So, the
    // constructed string will be of the form:
    //
    // name1=value1&name2=value2...
    // In addition the following requirements apply:
    //
    // Headers are sorted alphabetically.
    // All header names must be made uppercase before constructing the string.
    // Headers whose names don't start with "X-MCASH-" are not included.
    public function buildSignatureMessage($requestMethod, $url, $headers) {
        // Find headers that start with X-MCASH
        $mcashHeaders = array();
        foreach ($headers as $key => $value) {
            $ucKey = strtoupper($key);
            if (substr($ucKey, 0, 7) === "X-MCASH") {
                $mcashHeaders[$ucKey] = $value;
            }
        }

        // Sort headers by key
        ksort($mcashHeaders);

        // Create key value pairs 'key=value'
        $headerPairs = array();
        foreach ($mcashHeaders as $key => $value) {
            $headerPairs[] = sprintf("%s=%s", $key, $value);
        }

        // Join header pairs
        $headerString = implode("&", $headerPairs);

        return sprintf(
            "%s|%s|%s", strtoupper($requestMethod), $url, $headerString
        );
    }

    private function sign($requestMethod, $url, $headers) {
        $message = $this->buildSignatureMessage($requestMethod, $url, $headers);
        $crypto = Mage::helper("mcash/crypto");
        return $crypto->sign_pkcs1($this->_userPrivKey, $message);
    }

    protected function getClient()
    {
        $_client = new Zend_Http_Client($this->_url, $this->_config);
        $this->setHeader('Accept','application/json,application/vnd.mcash.api.merchant.v'.self::API_VERSION.'+json');
        $this->setHeader('Content-type','application/json');
        $this->setHeader('X-Mcash-Merchant',$this->_merchantId);
        $this->setHeader('X-Mcash-User',$this->_userId);
        $this->setHeader('X-Mcash-Timestamp',$this->utcTimestamp());

        if ($this->_test) {
            $this->setHeader('X-Testbed-Token', 's_Qu1gkYsZUvK-RvO43Ij02CYV-3wyMp6uUn0AodymQ');
        }
        return $_client;
    }

    private function setHeader($key, $value) {
        $this->_headers[$key] = $value;
    }
}
