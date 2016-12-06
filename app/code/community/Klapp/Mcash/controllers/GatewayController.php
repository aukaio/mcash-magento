<?php
	
class Klapp_Mcash_GatewayController extends Mage_Core_Controller_Front_Action {
	
	public function redirectAction(){	

		$order = new Mage_Sales_Model_Order();
		$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$order->loadByIncrementId($orderId);
		
		$payment = $order->getPayment();
		
		$amount = $order->getTotalDue();
        
        $api = Mage::getModel('mcash/api');
       
        $text = Mage::helper('mcash')->__( 'Order #%s', $payment->getOrder()->getIncrementId() ) . "\n" . $this->getProductLines( $payment->getOrder() );
		
        $result = $api->paymentRequest( $payment->getAdditionalInformation(Klapp_Mcash_Model_Payment_Mcash::MCASH_TOKEN), $amount, $payment->getOrder()->getId(), $text );

        if( !empty( $result->id ) ){
	        	        
	        $payment->setTransactionId( $result->id )
    
	        ->setCurrencyCode()
	        ->setPreparedMessage('')
	        ->setParentTransactionId( $result->id )
	        ->setShouldCloseParentTransaction( false )
	        ->setIsTransactionClosed( false );
	        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
	        $order->save();     
	        Mage_Core_Controller_Varien_Action::_redirectUrl($result->uri);
	        
        } else {
	        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
	        $order->save();	        
	        Mage::throwException(Mage::helper('mcash')->__('Communication with mCASH failed. Please try again or choose another payment method'));
        }			
	
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
	
}

?>