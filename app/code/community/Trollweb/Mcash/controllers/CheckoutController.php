<?php

class Trollweb_Mcash_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function statusAction() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $token = $quote->getPayment()->getAdditionalInformation('mcash_token');

        $jsonData = json_encode(array(
            "scanned" => !empty($token),
        ));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    public function requestPermissionsAction() {
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        if (!$quote) {
            $this->getResponse()->setHttpResponseCode(412);
            return;
        }

        $token = $quote->getPayment()->getAdditionalInformation('mcash_token');
        if (!$token) {
            $this->getResponse()->setHttpResponseCode(412);
            return;
        }

        $api = Mage::getModel('mcash/api');

        try {
            $data = $api->createPermissionRequest($token, uniqid(), 300, "openid shipping_address address profile phone email");
            $session->setPermissionRequestId($data["id"]);
        } catch (Exception $e) {
            Mage::log("Permission request failed: " . $e->getMessage());
            $this->getResponse()->setHttpResponseCode(500);
            return false;
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode(array("success" => true)));
    }

    public function userinfoAction() {
        $session = Mage::getSingleton('checkout/session');
        $id = $session->getPermissionRequestId();

        if (!$id) {
            $this->getResponse()->setHttpResponseCode(412);
            return;
        }

        $api = Mage::getModel('mcash/api');

        try {
            $outcome = $api->getPermissionRequestOutcome($id);
            $tokenType = $outcome["token_type"];
            $accessToken = $outcome["access_token"];
        } catch (Exception $e) {
            Mage::log("Permission request outcome failed: " . $e->getMessage());
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        if (!$accessToken) {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode(array(
                "ready" => false
            )));
            return;
        }

        $data = array();
        $oauthApi = Mage::getModel('mcash/api_oauth');

        try {
            $userinfo = (array)$oauthApi->getUserinfo($accessToken, $tokenType);

            foreach (array("name", "email", "phone_number") as $property) {
                if (array_key_exists($property, $userinfo)) {
                    $data[$property] = $userinfo[$property];
                }
            }
        } catch (Exception $e) {
            Mage::log("Get userinfo failed: " . $e->getMessage());
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        try {
            $addr = (array)$oauthApi->getShippingAddress($accessToken, $tokenType);

            foreach (array("locality", "postal_code", "street_address") as $property) {
                if (array_key_exists($property, $addr)) {
                    $data[$property] = $addr[$property];
                }
            }
        } catch (Exception $e) {
            Mage::log("Get shipping address failed: " . $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode(array(
            "ready" => true,
            "userinfo" => $data
        )));
    }

    public function qrAction() {
        $api = Mage::getModel('mcash/api');
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if (!$quote) {
            $this->getResponse()->setHttpResponseCode(400);
            return false;
        }

        $payment = $quote->getPayment();
        if (!$payment) {
            $this->getResponse()->setHttpResponseCode(400);
            return false;
        }

        if (!$payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK)) {
            if ($shortLink = $api->getShortLink($quote->getId())) {
                $payment->setAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK,$shortLink)->save();
            }
        }

        $url = $api->getQrImage($payment->getAdditionalInformation(Trollweb_Mcash_Model_Payment_Mcash::MCASH_SHORTLINK));

        $jsonData = json_encode(array(
            "url" => $url,
        ));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
