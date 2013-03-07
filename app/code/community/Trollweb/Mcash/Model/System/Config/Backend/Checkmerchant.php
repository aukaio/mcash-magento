<?php

class Trollweb_Mcash_Model_System_Config_Backend_Checkmerchant extends Mage_Core_Model_Config_Data
{
    
    protected function _beforeSave()
    {

        if ($this->getFieldsetDataValue('merchant_id') && $this->getFieldsetDataValue('pos_id')) {
            $api = Mage::getModel('mcash/api');
            $api->setSecret($this->getFieldsetDataValue('secret'));
            $api->setMerchantId($this->getFieldsetDataValue('merchant_id'));
            $api->setPosId($this->getFieldsetDataValue('pos_id'));

            
            if (!$api->posExists()) {
                if ($api->merchantExists()) {
                    // pos Does not exist.- create it.
                    if ($this->getStoreCode()) {
                        $name = Mage::app()->getStore($this->getStoreCode())->getName();
                    }
                    elseif ($this->getWebsiteCode()) {
                        $name = Mage::app()->getWebsite($this->getWebsiteCode())->getDefaultStore()->getName();
                    }
                    else {
                        $name = Mage::app()->getDefaultStoreView()->getName();
                    }


                    if ($api->createPos($this->getFieldsetDataValue('pos_id'),$name)) {
                        // Successfull
                        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mcash')->__('POS %s was created',$this->getFieldsetDataValue('pos_id')));
                    }
                    else {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mcash')->__('Unable to create POS : %s',$api->getErrorMessage()));
                    }
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mcash')->__('Unable to communicate with mCASH: %s',$api->getErrorMessage()));
                }
            }
        }

    }

}