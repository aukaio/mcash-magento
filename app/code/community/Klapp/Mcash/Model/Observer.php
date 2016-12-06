<?php
	
class Klapp_Mcash_Model_Observer {
	
	public function reauthOrders(){
		
		$orders = Mage::getModel('sales/order')->getCollection();
		$orders->addFieldToFilter('status','processing');
		$orders->getSelect()->join(
		    array('p' => $orders->getResource()->getTable('sales/order_payment')),
		    'p.parent_id = main_table.entity_id',
		    array()
		);
		$orders->addFieldToFilter('method','mcash');

		Mage::log('Running reauthentication cronjob for mCASH on ' . count($orders) . ' orders');
		
		foreach( $orders AS $order ){
			
			$payment = $order->getPayment();	
			
			$transaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
			->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
			->addAttributeToFilter('txn_type', array('eq' => 'authorization'));
			
			$transaction_array = $transaction->toArray();
			
			$mcash_tid = ( isset( $transaction_array['items'][0] ) ) ? $transaction_array['items'][0]['txn_id'] : false;
			
			if( !$mcash_tid ) continue;

			$api = Mage::getModel('mcash/api');
			
			$api->reauthenticate($mcash_tid);
			
		}
		
	}
	
}