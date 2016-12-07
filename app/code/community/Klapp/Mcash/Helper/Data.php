<?php
	
class Klapp_Mcash_Helper_Data extends Mage_Core_Helper_Abstract {


    public function getConfig($field,$storeId=null)
    {
        return Mage::getStoreConfig('payment/mcash/'.$field,$storeId);
    }
    
    public function saveConfig($field, $value, $scope = 'default', $scopeId = 0)
    {
    	Mage::getConfig()->saveConfig('payment/mcash/'.$field, Mage::helper('core')->encrypt($value), $scope, $scopeId);
    }
    
   	public function getLogo(){
	   return $this->getSkinUrl('mcash/mcashlogo.svg', array('_secure' => true));
   	} 
    
    public function isPartial($object) {
        $origList = array();
        if ($object instanceof Mage_Sales_Model_Order_Creditmemo) {
            foreach ($object->getInvoice()->getAllItems() as $item) {
                $origList[$item->getOrderItemId()] = $item->getQty();
            }
        }
        elseif ($object instanceof Mage_Sales_Model_Order_Invoice) {
            foreach ($object->getOrder()->getAllItems() as $item) {
                $origList[$item->getId()] = $item->getQtyOrdered();
            }
        }
        $objectList = $object->getAllItems();
        if (count($objectList) != count($origList)) {
            return true;
        }
        foreach ($objectList as $item) {
            if (isset($origList[$item->getOrderItemId()])) {
                if ($origList[$item->getOrderItemId()] - $item->getQty() > 0) {
                    return true;
                }
            }
        }
        return false;
    }

	
}
	
?>