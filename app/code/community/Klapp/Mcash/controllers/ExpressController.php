	<?php
	
class Klapp_Mcash_ExpressController extends Mage_Core_Controller_Front_Action {
	
	public function checkoutAction(){
		
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();
		
		// Get the current cart session
		$cart = Mage::getSingleton('checkout/session')->getQuote();
		
		// Start New Sales Order Quote
		$quote = Mage::getModel('sales/quote')->setStoreId($store->getId());

		$quote->setCurrency('NOK');
		
		$session = Mage::getSingleton('customer/session');
		
		if(!$session->isLoggedIn()){
			$customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail('tempcustomer@mcash.no');
				
			if( !$customer->email ){
				
			    $customer = Mage::getModel('customer/customer');
			    $customer->setWebsiteId($websiteId)
			            ->setStore($store)
			            ->setFirstname(' ')
			            ->setLastname(' ')
			            ->setEmail('tempcustomer@mcash.no')
			            ->setPassword("password");
			    $customer->save();	
			  		
			}			
			
		    $address = array(
			    'customer_address_id' => '',
			    'prefix' => '',
			    'firstname' => 'Avventer',
			    'middlename' => ' ',
			    'lastname' => 'detaljer',
			    'suffix' => '',
			    'company' => '',
			    'street' => array(
			        '0' => 'Avventer detaljer fra mCASH',
			        '1' => ' '
			    ),
			    'city' => 'Oslo',
			    'country_id' => 'NO',
			    'region' => '',
			    'postcode' => '0000',
			    'telephone' => '+4790000000',
			    'fax' => '',
			    'vat_id' => '',
			    'save_in_address_book' => 1		    
		    );			
			
		} else {
			$customer = Mage::getModel('customer/customer')->load($session->getId());
			$billing = $customer->getDefaultBillingAddress();
            $address = array(
                    'firstname' => $billing->getFirstname(),
                    'lastname' => $billing->getLastname(),
                    'street' => $billing->getStreet(),
                    'city' => $billing->getCity(),
                    'postcode' => $billing->getPostcode(),
                    'telephone' => $billing->getTelephone(),
                    'country_id' => $billing->getCountryId(),
                    'region_id' => $billing->getRegionId(), // id from directory_country_region table
            );			
		} 
		
	    $quote->assignCustomer($customer);
	   
		$quote->setSendCconfirmation(1);
		
		// Add the products from the cart to the quote
		foreach( $cart->getAllItems() as $item ) { 
		    if ($item->getParentItemId()) {
		        continue;
		    }			
			$product = Mage::getModel('catalog/product')->load($item->getProductId());
			$quote->addProduct($product, new Varien_Object(array('qty' => $item->getQty())));
		}

		// Set Sales Order Billing Address
		$billingAddress = $quote->getBillingAddress()->addData($address);
		
		// Set Sales Order Shipping Address
		$shippingAddress = $quote->getShippingAddress()->addData($address);

		
		// Collect Rates and Set Shipping & Payment Method
		$shippingAddress->setCollectShippingRates(true)
		        ->collectShippingRates()
		        ->setShippingMethod('flatrate_flatrate')
		        ->setPaymentMethod('mcash');
		        
		
		
		// Set Sales Order Payment
		$quote->getPayment()->importData(array('method' => 'mcash'));
		
		// Collect Totals & Save Quote
		$quote->collectTotals()->save();
		
		try {
		    // Create Order From Quote
		    $service = Mage::getModel('sales/service_quote', $quote);
		    $service->submitAll();
		    $increment_id = $service->getOrder()->getRealOrderId();
		}
		catch (Exception $ex) {
		    echo $ex->getMessage();
		}
		catch (Mage_Core_Exception $e) {
		    echo $e->getMessage();
		}
	
		
		$order = new Mage_Sales_Model_Order();
		$order->loadByIncrementId($increment_id);
		
		$session = Mage::getSingleton('checkout/session');

		$session->setLastQuoteId($quote->getId())->setLastSuccessQuoteId($quote->getId());
		$session->setLastOrderId($order->getId());
		
		$payment = $order->getPayment();
		
		$amount = $order->getTotalDue();
        
        $api = Mage::getModel('mcash/api');
		
        $text = Mage::helper('mcash')->__( 'Order #%s', $payment->getOrder()->getIncrementId() ) . "\n" . $this->getProductLines( $payment->getOrder() );
		
        $result = $api->paymentDetailedRequest( $payment->getAdditionalInformation(Klapp_Mcash_Model_Payment_Mcash::MCASH_TOKEN), $amount, $payment->getOrder()->getId(), $text );
		
        if( !empty( $result->id ) ){
	        	        
	        $payment->setTransactionId( $result->id )
    
	        ->setCurrencyCode()
	        ->setPreparedMessage('')
	        ->setParentTransactionId( $result->id )
	        ->setShouldCloseParentTransaction( false )
	        ->setIsTransactionClosed( false );
	        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
	        $order->save();     
	        
	        // Remove the products from the cart
			foreach( $cart->getAllItems() as $item ) { 
			    if ($item->getParentItemId()) {
			        continue;
			    }			
				Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
			}
	        
	        
	        // Mark checkout as success
	        Mage::getSingleton('customer/session')->setRedirectUrl( $result->uri );
	        
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