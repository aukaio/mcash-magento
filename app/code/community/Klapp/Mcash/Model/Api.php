<?php
	
require_once( BP.DS.'app/code/community/Klapp/Mcash/Model/Api/php-sdk/mcash.php' );

class Klapp_Mcash_Model_Api extends Varien_Object {

	public $_api;

	function __construct(){
		$this->api = new \mCASH\mCASH;
		
		// Setting API Authentication level and secret/key
		// Setting API Authentication level and secret/key
		mCASH\mCASH::setApiLevel('KEY');
		mCASH\mCASH::setApiSecret(Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_priv_key')));
		
		// Merchant and user id
		mCASH\mCASH::setMerchantId(Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('merchant_id')));
		mCASH\mCASH::setUserId(Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_id')));	

       
        if( Mage::helper('mcash')->getConfig('test') ){
	        mCASH\mCASH::setTestEnvironment( true );
	        mCASH\mCASH::setTestToken( Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('test_token')) );	        
	        mCASH\mCASH::setApiSecret( Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('test_priv_key')) );       
        }
	}
	
	public function capturePayment( $transaction_id ){
		
		$payment_request = \mCASH\PaymentRequest::retrieve( $transaction_id );

		if( !$payment_request ) {
			 Mage::log('Did not find transaction with id ' . $transaction_id );
			 Mage::throwException(Mage::helper('mcash')->__('No transaction with the given transaction ID could be found.'));
		}
		
		return $payment_request->capture();
		
	}
	
	public function paymentRequest( $additional_information, $amount, $order_id, $text ){
		
		$data = array(
		    'success_return_uri'    => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'checkout/onepage/success/',
		    'failure_return_uri'    => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'checkout/onepage/failure/',
		    'callback_uri'			=> Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'mcash/callback/update/',
		    'allow_credit'          => true,
		    'pos_id'                => 'mcash_express',
		    'pos_tid'               => $order_id,
		    'action'                => 'sale',
		    'amount'                => $amount,
		    'text'                  => $text,
		    'currency'              => 'NOK'			
		);

		$request = \mCASH\PaymentRequest::create($data);		
		
		return $request;
		
	}
	
	public function reauthenticate( $mcash_tid ){
		Mage::log('mcash_api_reauthenticate');
	    try {
		    Mage::log('Calling php-sdk');
		    $payment_request = \mCASH\PaymentRequest::retrieve($mcash_tid);
		   	$res = $payment_request->reauthorize();	 
		   	Mage::log('Reauthenticated ' . $mcash_tid);
	    } catch( \Exception $e ){
		   	Mage::log('Reauthentication failed: ' . $e->getMessage()); 
	    }
	}
	
	public function paymentDetailedRequest( $additional_information, $amount, $order_id, $text ){
		
		$data = array(
		    'success_return_uri'    => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'checkout/onepage/success/',
		    'failure_return_uri'    => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'checkout/onepage/failure/',
		    'callback_uri'			=> Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'mcash/callback/update/',
		    'allow_credit'          => true,
		    'pos_id'                => 'mcash_express',
		    'pos_tid'               => $order_id,
		    'action'                => 'sale',
		    'amount'                => $amount,
		    'text'                  => $text,
		    'currency'              => 'NOK', 
		    'required_scope'	 	=> 'openid phone email shipping_address'		
		);

		$request = \mCASH\PaymentRequest::create($data);		
		
		return $request;
				
	}
	
	public function refundPayment( $transaction_id, $amount, $refund_id ){
	
		$payment_request = \mCASH\PaymentRequest::retrieve( $transaction_id );
		$payment_request->amount = $amount;
		$payment_request->refund_id = "$refund_id"; // Escaped since the api expects a string
		$payment_request->additional_amount = "0"; // Escaped since the api expects a string
		$payment_request->currency = 'NOK';
		
		if( !$payment_request ) {
			 Mage::log('Did not find transaction with id ' . $transaction_id );
			 Mage::throwException(Mage::helper('mcash')->__('No transaction with the given transaction ID could be found.'));
		}
		
		return $payment_request->refund();
		
	}
	
	public function releasePayment( $transaction_id, $amount, $refund_id ){
		Mage::log('API-Model: ' . $transaction_id . ' - ' . $amount . ' - ' . $refund_id, null, 'mcash.log');
		$payment_request = \mCASH\PaymentRequest::retrieve( $transaction_id );
		return $payment_request->release();
	}
	
	protected function getConnection(){
		
		if( !isset( $this->_api ) ){
			// Connect to the API
		}
		
		return $this->_api;
		
	}

}
