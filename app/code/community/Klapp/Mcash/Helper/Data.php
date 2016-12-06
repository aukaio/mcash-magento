<?php
	
class Klapp_Mcash_Helper_Data extends Mage_Core_Helper_Abstract {

    const MCASH_PUB_KEY_PROD = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyGr/0kllDmLNq8KblWJt
Ths43xqlj0q++xWdHjZKL/6Ko1/NouQsWCVhtoRvAwKWc8TKhVDfRn3an7zBnnyD
/9BXiHoN2OFfogwlY/EAHX4MbKR/0Ankqo5OPG875IpqrZJvWZ/1/NG5epuJAWYG
dxrlaS0QqueX8sl77bAA5U7CYEvUswiFQ3Fegm2xJzVYgTh81ScfPw8G+JyugxCR
C/guFdebyYqSGLRoC/A7oUrEyqUr04PSx8J1Axbp46ml0l6M9cS5e1YRyYREAB14
hxeVSYbgALaCSD+44YeN5XWgzqezocGdilNumPaQW1iVeRAgdTginTgk4rHohynp
AwIDAQAB
-----END PUBLIC KEY-----';
    const MCASH_PUB_KEY_TEST = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8Pg5kMWZzX0U+ZGts6Ws
oLrI1bN5PjXzFRAPza19qYrONVxhFlJx8AQWohISL1hKVPJCMuyQKhs0/2jtWk+E
mDHXFafW+kYV7lseznj6nW49VFyxHYdQDNHgpyUA5p+lmZABbmcKGabw/Cp28vtH
im4zWBGVXnQ7UPm1peMzeuaB7L246J+ZcfLpd3trSWg2mywB23rqELzTNKi0s7cb
kS+2gk5B72q3qcaTO47rPgEVcUTB2A+jxcu6rOVFCbhQ8+JkLDPeHPDuIBQ5mAwN
XLY+3ffovc31S5cMhquiKaYYwiuxeI23AWtNV2FoD00bm4q+5XCuBGgPJf3nkNYV
eQIDAQAB
-----END PUBLIC KEY-----';

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
    
    public function isProduction() {
        return !$this->getConfig('test');
    }
    public function getMcashPublicKey() {
        return $this->isProduction() ? self::MCASH_PUB_KEY_PROD : self::MCASH_PUB_KEY_TEST;
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