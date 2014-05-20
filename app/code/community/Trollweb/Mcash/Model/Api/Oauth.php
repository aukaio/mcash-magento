<?php

class Trollweb_Mcash_Model_Api_Oauth extends Mage_Core_Model_Abstract {
    public function getUserinfo($accessToken, $tokenType) {
        $url = $this->getBaseUrl() . "/userinfo";
        $client = $this->createClient($url);

        $res = $this->get($client, array(
           "Authorization" => $tokenType . " " . $accessToken
        ));

        if (!$res->isSuccessful()) {
            Mage::throwException("Failed to get userinfo");
        }

        return json_decode($res->getBody());
    }

    public function getShippingAddress($accessToken, $tokenType) {
        $url = $this->getBaseUrl() . "/scope/shipping_address";
        $client = $this->createClient($url);

        $res = $this->get($client, array(
           "Authorization" => $tokenType . " " . $accessToken
        ));

        if (!$res->isSuccessful()) {
            Mage::throwException("Failed to get shipping address");
        }

        return json_decode($res->getBody());
    }

    private function createClient($url, $timeout=15) {
        return new Zend_Http_Client($url, array(
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => max(0, $timeout - 5),
                CURLOPT_TIMEOUT => $timeout,
            ),
        ));
    }

    private function get($client, $headers=array()) {
        foreach ($headers as $key => $value) {
            $client->setHeaders($key, $value);
        }

        // Add testbed token header if we are using the test api
        $config = Mage::helper("mcash/config");
        if ($config->useTestApi()) {
            $client->setHeaders("X-Testbed-Token", $config->getTestbedToken());
        }

        return $client->request("GET");
    }

    private function getBaseUrl() {
        $config = Mage::helper("mcash/config");
        return implode("/", array(
            $config->getApiBaseUrl(),
            "oauth2",
            $config->getOauthApiVersion()
        ));
    }
}
