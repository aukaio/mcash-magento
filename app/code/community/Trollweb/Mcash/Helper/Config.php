<?php

class Trollweb_Mcash_Helper_Config extends Mage_Core_Helper_Abstract {
    public function useTestApi() {
        return Mage::getStoreConfig("payment/mcash/test");
    }

    public function getApiBaseUrl() {
        $key = $this->useTestApi() ? "trollweb_mcash/api/baseurl_test" : "trollweb_mcash/api/baseurl_prod";
        return Mage::getStoreConfig($key);
    }

    public function getOauthApiVersion() {
        return Mage::getStoreConfig("trollweb_mcash/api/oauth_version");
    }

    public function getTestbedToken() {
        return Mage::getStoreConfig("trollweb_mcash/api/testbed_token");
    }
}
