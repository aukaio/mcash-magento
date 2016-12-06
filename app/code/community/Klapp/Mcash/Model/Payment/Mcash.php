<?php
	
class Klapp_Mcash_Model_Payment_Mcash extends Mage_Payment_Model_Method_Abstract {
	
    protected $_code = 'mcash';
    protected $_formBlockType = 'mcash/form';
    protected $_infoBlockType = 'mcash/info';
    /**
     * Payment Method features
     * @var bool
     */
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;

    protected $_returnUrl;
    
	const MCASH_TOKEN = 'mcash_token';

    public function validate()
    {
        parent::validate();
        $payment = $this->getInfoInstance();
        return $this;
    }
    
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('mcash/gateway/redirect/', array('_secure' => false));
		//return Mage::getSingleton('customer/session')->getRedirectUrl();
	}

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);            
        
    }
    
    public function order(Varien_Object $payment, $amount)
    {

        $api = Mage::getModel('mcash/api');
        $text = Mage::helper('mcash')->__( 'Order #%s', $payment->getOrder()->getIncrementId() ) . "\n" . $this->getProductLines( $payment->getOrder() );
		
        $result = $api->paymentRequest( $payment->getAdditionalInformation(self::MCASH_TOKEN), $amount, $payment->getOrder()->getId(), $text );

        if( !empty( $result->id ) ){	        
	        $payment->setTransactionId( $result->id );
	        $payment->setIsTransactionClosed( false );         
	        Mage::getSingleton('customer/session')->setRedirectUrl( $result->uri );
        } else {
	        Mage::throwException(Mage::helper('mcash')->__('Communication with mCASH failed. Please try again or choose another payment method'));
        }	    
        
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $helper = Mage::helper("mcash");
        $invoice = Mage::registry('current_invoice');
        // Grab mcash transaction id from parent transaction
        $transactionId = $payment->getParentTransactionId();
        if ($helper->isPartial($invoice)) {
            // Create a "random" capture id with the transaction id as a prefix
            $captureId = uniqid($transactionId . "-");
            $payment->setTransactionId($captureId);
            $this->capturePartial($transactionId, $amount, $captureId);
        } else {
            $this->captureFull($transactionId);
        }
    }

    private function captureFull($transactionId) {
        $api = Mage::getModel('mcash/api');
        $isOk = $api->capturePayment( $transactionId );
        if (!$isOk) {
            Mage::throwException(Mage::helper('mcash')->__('Capture failed, status is not ok'));
        }
    }
    
    private function capturePartial($transactionId, $amount, $captureId) {
        $api = Mage::getModel('mcash/api');

    }
    
    public function refund(Varien_Object $payment, $amount){
	     Mage::log('Refunding order - Refunding Payment', null, 'mcash.log');
	    $api = Mage::getModel('mcash/api');
	    $helper = Mage::helper("mcash");
	    $authorization = $payment->getAuthorizationTransaction();
	    $prnt = $authorization->getParentTransaction();
		
	    $result = $api->refundPayment( $prnt->getTxnId(), $amount, $payment->getLastTransId() );
	    if( !$result ){
		    Mage::throwException(Mage::helper('mcash')->__('Refund failed, status is not ok'));
	    }
	    
	    return $this;
	    
    }
    
    public function cancel(Varien_Object $payment){
	    
	    Mage::log('Cancelling order - Releasing Payment', null, 'mcash.log');
	    
	    $api = Mage::getModel('mcash/api');
	    $helper = Mage::helper("mcash");

	    $result = $api->releasePayment( $payment->getLastTransId(), $amount, $payment->getLastTransId() );
	    if( !$result ){
		    Mage::throwException(Mage::helper('mcash')->__('Release failed, status is not ok'));
	    }	    
	    
	    return $this;
    }
    
    public function getProductLines($order) {
        $text = "";
        foreach ($order->getAllItems() as $product) {
            if (!$product->getParentItemId()) {
              $text .= (int)$product->getQtyOrdered().' x '.$product->getName()."\n";
            }
        }
        return $text;
    } 
    
    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId($payment->getLastTransId());
        return $this;
    }                    	
	
}