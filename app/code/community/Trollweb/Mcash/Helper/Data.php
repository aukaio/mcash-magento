<?php

class Trollweb_Mcash_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getConfig($field,$storeId=null)
    {
        return Mage::getStoreConfig('payment/mcash/'.$field,$storeId);
    }
  
}