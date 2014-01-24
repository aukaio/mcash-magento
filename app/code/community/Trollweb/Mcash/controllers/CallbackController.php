<?php

class Trollweb_Mcash_CallbackController extends Mage_Core_Controller_Front_Action
{
    public function shortlinkAction()
    {
        $request = $this->getRequest();

        $quoteid = $request->getParam('id',false);
        $data =  $request->getRawBody();

        if (!$quoteid) {
            $this->_errorResponse('No Quote ID given');
            return;
        }

        if (!$data) {
            $this->_errorResponse('No Data');
            return;
        }      


        if (!$this->validateSignature($request)) {
            Mage::log("[mCASH] Invalid signature");
            $this->_errorResponse('Invalid signature');
            return;
        }

        $jsonData = Mage::helper('core')->jsonDecode($data);
        if (!$jsonData) {
            $this->_errorResponse('Request is not a valid json format');
            return;
        }

        $quote = Mage::getModel('sales/quote')->load($quoteid);   
        if (!$quote->getId()) {
            $this->_errorResponse('Unable to find Quote with ID '.$quoteid);
            return;
        }

        $token = $jsonData['object']['id'];
        $quote->getPayment()->setAdditionalInformation('mcash_token', $token)->save();

        $result = array(
            'text' => 'Scan registered'
        );        

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    private function validateSignature($request) {
        $requestMethod = $request->getServer('REQUEST_METHOD');
        $absUrl = $this->buildAbsoluteUrl($request->getRequestUri());
        $headers = apache_request_headers();

        $message = Mage::getModel("mcash/api_client")->buildSignatureMessage($requestMethod, $absUrl, $headers);
        $key = Mage::helper("mcash")->getMcashPublicKey();
        $authorization = $request->getHeader('Authorization');
        list($sigType, $signature) = explode(" ", $authorization);
        return Mage::helper("mcash/crypto")->verify_signature_pkcs1($key, $message, $signature);
    }

    protected function _errorResponse($message)
    {
        $this->getResponse()->setHttpResponseCode(404);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('text' => 'Invalid request')));
        Mage::log('Invalid mCash request: '.$message,Zend_Log::ERR);
    
    }

    private function buildAbsoluteUrl($relativeUrl) {
        $baseUrl = Mage::getBaseUrl("link", true);
        return rtrim($baseUrl, "/") . $relativeUrl;
    }
}
