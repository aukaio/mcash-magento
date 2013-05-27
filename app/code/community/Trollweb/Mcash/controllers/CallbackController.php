<?php

class Trollweb_Mcash_CallbackController extends Mage_Core_Controller_Front_Action
{
    
    public function shortlinkAction()
    {
        $quoteid = $this->getRequest()->getParam('id',false);
        $data =  $this->getRequest()->getRawBody();

        if (!$quoteid) {
            $this->_errorResponse('No Quote ID given');
            return;
        }

        if (!$data) {
            $this->_errorResponse('No Data');
            return;
        }      

        /*
        if (!Mage::getModel('mcash/api_client')->verifySignature($this->getRequest()->getServer('HTTP_X_MCASH_SIGNATURE'),$data)) {
            $this->_errorResponse('Invalid hash');
            return;
        }
        */
        
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

        $quote->getPayment()->setAdditionalInformation('mcash_token',$jsonData['id'])->save();

        $result = array(
            'text' => 'Scan registered'
        );        

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }


    protected function _errorResponse($message)
    {
        $this->getResponse()->setHttpResponseCode(404);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('text' => 'Invalid request')));
        Mage::log('Invalid mCash request: '.$message,Zend_Log::ERR);
    
    }

}
