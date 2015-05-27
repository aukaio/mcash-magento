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


        //if (!$this->validateSignature($request)) {
        //    Mage::log("mCASH Invalid signature");
        //    $this->_errorResponse('Invalid signature');
        //    return;
        //}

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

        if (Mage::helper('mcash')->getConfig('ask_for_scopes')){
            $result = array(
                'text' => 'Scan registered'
            );
        } else {
            $result = array(
                'text' => __('Scan registered. Please continue in the webshop.')
            );
        };

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    private function validateSignature($request) {
        //this is broken. Needs to look at the payload to.
        $requestMethod = $request->getServer('REQUEST_METHOD');
        $absUrl = $this->buildAbsoluteUrl($request->getRequestUri());
        $headers = $this->request_headers();
        if( !array_key_exists('Authorization', $headers) ) {
            Mage::log('mCASH validateSignature() failed due to missing Authorization header. mCASH Authorization header is not in http basic authentication format, and will be discarded by apache, if apache is not configured accordingly');
            return -1;
        } else {
            Mage::log('mCASH validateSignature() Authorization header present');
        }
        $message = Mage::getModel("mcash/api_client")->buildSignatureMessage($requestMethod, $absUrl, $headers);
        $key = Mage::helper("mcash")->getMcashPublicKey();
        $authorization = $request->getHeader('Authorization');
        list($sigType, $signature) = explode(" ", $authorization);
        $retval = Mage::helper("mcash/crypto")->verify_signature_pkcs1($key, $message, $signature);
        Mage::log('mCASH validateSignature() headers = ' . print_r($headers, true));
        Mage::log('mCASH validateSignature() key     = ' . $key);
        Mage::log('mCASH validateSignature() requestMethod = ' . $requestMethod);
        Mage::log('mCASH validateSignature() message = ' . $message);
        Mage::log('mCASH validateSignature() signature = ' . $signature);
        Mage::log('mCASH validateSignature() retval = ' . $retval);
        return $retval;
    }

    protected function _errorResponse($message)
    {
        $this->getResponse()->setHttpResponseCode(404);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('text' => 'Invalid request')));
        Mage::log('Invalid mCASH request: '.$message,Zend_Log::ERR);
    
    }

    private function buildAbsoluteUrl($relativeUrl) {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
        return rtrim($baseUrl, "/") . $relativeUrl;
    }

    private function getallheaders() { 
        foreach($_SERVER as $K=>$V){$a=explode('_' ,$K); 
            if(array_shift($a)=='HTTP'){ 
                array_walk($a,function(&$v){$v=ucfirst(strtolower($v));});
                $retval[join('-',$a)]=$V;
            }
        }
        if(isset($_SERVER['CONTENT_TYPE'])) $retval['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        if(isset($_SERVER['CONTENT_LENGTH'])) $retval['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        return $retval;
    }
    
    private function request_headers(){
        if( function_exists('apache_request_headers') ) {
            Mage::log('mCASH request_headers() apache_request_headers() is defined.');
            return apache_request_headers();
        } else {
            Mage::log('mCASH  request_headers() apache_request_headers() is not defined. Using fallback');
            return $this->getallheaders();
        }
    }
    
}
