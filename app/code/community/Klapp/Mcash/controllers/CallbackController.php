<?php
	
class Klapp_Mcash_CallbackController extends Mage_Core_Controller_Front_Action {
	
	public function updateAction(){
		
		Mage::log('CallbackController called', null, 'mcash.log');
		$api = Mage::getModel('mcash/api');
		
       	// Get the input from mCASH
       	@ob_clean();
       	Mage::log('Retrieving input data', null, 'mcash.log');
       	$body = file_get_contents('php://input');
       	Mage::log('CallbackController called with body: ' . $body, null, 'mcash.log');
       	$payload = json_decode( $body );
       	Mage::log('CallbackController called with payload: ' . json_encode( $payload ), null, 'mcash.log');
	   	// Get the Transaction ID from the uri
	   	$parts = explode("/", $payload->meta->uri );
        $mcash_tid = $parts[count($parts)-3];
				
		// Malformed request. Missing ID
		if ( !$payload->meta->id ) {
			Mage::log('Missing the ID. Aborting with 400 Bad Request', null, 'mcash.log');  
			header('HTTP/1.1 400 Bad Request');
			exit;
		}   
		
		// Validate the incoming request headers and payload
		$headers = \mCASH\Utilities\Headers::request_headers();
		if( array_key_exists( 'Authorization', $headers ) ){

			Mage::log( 'Authorization header exists', null, 'mcash.log' );
			Mage::log( 'Validation of method: ' . $_SERVER['REQUEST_METHOD'], null, 'mcash.log' );

			Mage::log( 'Validation of headers: ' . json_encode( $headers ), null, 'mcash.log' );
			
		    $s = $_SERVER;
	        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
	        $sp = strtolower($s['SERVER_PROTOCOL']);
	        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
	        $port = $s['SERVER_PORT'];
	        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
	        $host = (isset($use_forwarded_host) && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
	        $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
	        $url = $protocol . '://' . $host . $s['REQUEST_URI'];	  			
			
			if( !\mCASH\Utilities\Encryption::validateHeaders($_SERVER['REQUEST_METHOD'], $url, $headers, $body ) ){
				Mage::log( 'Validation of incoming headers failed. Aborting with 401 Unauthorized', null, 'mcash.log' );
				header('HTTP/1.1 401 Unauthorized');
				exit;
			} 
			
		} else {
			Mage::log( 'Validation of incoming headers failed. Aborting with 401 Unauthorized', null, 'mcash.log' );
			header('HTTP/1.1 401 Unauthorized');
			exit;			
		}
		
		try {
			
			// Get the payment request for the transaction
			$payment_request = \mCASH\PaymentRequest::retrieve( $mcash_tid );	
			
			// Get the outcome of the transaction
			$outcome = $payment_request->outcome();
			
			// Get the WC Order
			$order_id = $outcome->pos_tid;
			Mage::log( 'ORDER ID: ' . $order_id, null, 'mcash.log' );

			// Check if this is a authorization renewal. No need to run the entire job below if its only a renewal of an existing payment at the payment gateway.
			if( $payload->meta->event && $payload->meta->event == "payment_authorization_renewed" ){
				Mage::log('Payment for TID ' . $mcash_tid . ' for order ' . $order_id . ' have been renewed');
			}

			// The payment request have been authenticated with status "auth". The money still havent been captured
			else if( $outcome->status == "auth" ){
				
				Mage::log('Outcome is Auth', null, 'mcash.log');
				
				// UPDATE THE ORDER
				$order = Mage::getModel('sales/order')->load($order_id);	
				
				// Check if the order is assigned to the mcash default user. If so, fetch the collected data from the app, and reassign the order to the new customer
				if( $order->getCustomerEmail() === "tempcustomer@mcash.no" ){
					
					Mage::log('The order was received via the mCASH default user. Reassigning order', null, 'mcash.log' );
					
					// Create the customer
					$websiteId = Mage::app()->getWebsite()->getId();
					$store = Mage::app()->getStore();
					
					$address_exists_in_scope = ( isset( $outcome->permissions['user_info']['shipping_address'] ) && !empty( $outcome->permissions['user_info']['shipping_address'] ) );
					// If the address is not yet available, we will try to refetch it every 1 seconds for 10 seconds until its there
					if( !$address_exists_in_scope ){
						
						for( $i=0; $i<=10; $i++ ){
							sleep(1); //Pause for a second
							// Get the outcome again
							$outcome = $payment_request->outcome();
							if( isset( $outcome->permissions['user_info']['shipping_address'] ) && !empty( $outcome->permissions['user_info']['shipping_address'] ) ){
								$address_exists_in_scope = true;
								break;
							}
						}	
										
					}
					// Now we should have the address. However, if we dont, respond with a 500 error and let the callback retry
					if( !$address_exists_in_scope ){
						Mage::log( 'Address not received. Aborting with 500 Service Unavailable', null, 'mcash.log' );
						header( 'HTTP/1.1 500 Service Unavailable' );
						exit;					
					}
					Mage::log('The outcome permissions: ' . json_encode( $outcome->permissions, true ), null, 'mcash.log');
					// If we are still here, we have the address. Lets update the order with it
					// Since mCASH returns the full name as a string, we need to split it
					Mage::log( 'Address received. Commencing update', null, 'mcash.log' );	
					
					// First, lets check if a customer already exists with this email address. If not, we create a new one
					$customer = Mage::getModel('customer/customer')
		            ->getCollection()
		            ->addAttributeToSelect('*')
		            ->addAttributeToFilter('email', $outcome->permissions['user_info']['email'] )
		            ->getFirstItem();
		       
		            if( $customer ){
			            Mage::log('Customer with email already exists. Updating shipping info', null, 'mcash.log');
			            $billing = $customer->getDefaultBillingAddress();
			            if( $billing ){
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
			            } else {
							$name_parts = explode( " ", $outcome->permissions['user_info']['shipping_address']['name'] );
							$lastname = $name_parts[count($name_parts)-1];
							array_pop( $name_parts ); // Remove the last name from the array
							$firstname = implode( " ", $name_parts );
							// Create an address array to pass to the Order object
				            $address = array(
				                    'firstname' => $firstname,
				                    'lastname' => $lastname,
				                    'street' => $outcome->permissions['user_info']['shipping_address']['street_address'],
				                    'city' => $outcome->permissions['user_info']['shipping_address']['locality'],
				                    'postcode' => $outcome->permissions['user_info']['shipping_address']['postal_code'],
				                    'telephone' => $outcome->permissions['user_info']['phone_number'],
				                    'country_id' => $outcome->permissions['user_info']['shipping_address']['country']
				            );					            
			            }
		            } else {
						Mage::log( 'Creating a new customer', null, 'mcash.log' );
						$name_parts = explode( " ", $outcome->permissions['user_info']['shipping_address']['name'] );
						$lastname = $name_parts[count($name_parts)-1];
						array_pop( $name_parts ); // Remove the last name from the array
						$firstname = implode( " ", $name_parts );
						// Create an address array to pass to the Order object
			            $address = array(
			                    'firstname' => $firstname,
			                    'lastname' => $lastname,
			                    'street' => $outcome->permissions['user_info']['shipping_address']['street_address'],
			                    'city' => $outcome->permissions['user_info']['shipping_address']['locality'],
			                    'postcode' => $outcome->permissions['user_info']['shipping_address']['postal_code'],
			                    'telephone' => $outcome->permissions['user_info']['phone_number'],
			                    'country_id' => $outcome->permissions['user_info']['shipping_address']['country']
			            );											
					    $customer = Mage::getModel('customer/customer');
					    $customer->setWebsiteId($websiteId)
					            ->setStore($store)
					            ->setFirstname($firstname)
					            ->setLastname($lastname)
					            ->setEmail($outcome->permissions['user_info']['email'])
					            ->setPassword("password");
					    $customer->save();			
					    $customer->setBillingAddress($address);
					    $customer->setShippingAddress($address);	
					    $customer->save();								
					}
					
					Mage::log( 'Assigning order to customer ' . $customer->getId(), null, 'mcash.log' );
					
					$order->setCustomerId($customer->getId());
					$order->setCustomerFirstname($customer->getFirstname());
					$order->setCustomerLastname($customer->getLastname());
					$order->setCustomerEmail($customer->getEmail());

					// Assign the newly created customer to the order
					Mage::log('Updating shipping address', null, 'mcash.log');
					// Set Sales Order Billing Address
					$order->getBillingAddress()->addData($address);
					
					// Set Sales Order Shipping Address
					$order->getShippingAddress()->addData($address);
					  
					$order->save();
					
				}

				// Send Order Email
				$order->sendNewOrderEmail();
				
				Mage::log('Updating transaction information', null, 'mcash.log');
	
				$payment = $order->getPayment();	
				$payment->setTransactionId( $mcash_tid )
		        ->setCurrencyCode()
		        ->setPreparedMessage('')
		        ->setParentTransactionId( $mcash_tid )
		        ->setShouldCloseParentTransaction( false )
		        ->setIsTransactionClosed( false );
				$payment->authorize( true, $outcome->amount );	
				
				Mage::log('Finishing up. Saving order', null, 'mcash.log');
				$order->save();
				Mage::log('Update completed', null, 'mcash.log');

			}
			
			// If the customer declined the order i mCASH, we will just cancel it
			if( $outcome->status == "fail" ){
				Mage::log('outcome fail', null, 'mcash.log');
				$order = Mage::getModel('sales/order')->load($order_id);
		        //$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
		        $order->cancel();
		        $order->save();
			}
			
            header('HTTP/1.1 204 No Content');
            exit;			
							
		} catch( Exception $e ){
			Mage::log('Exception thrown:' . $e->getMessage(), null, 'mcash.log');
			header('HTTP/1.1 503 Service Unavailable');
			exit;
		}

	}	
	
}

?>