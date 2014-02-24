<?php

class Trollweb_Mcash_Block_Mcash extends Mage_Core_Block_Template {
    public function getBaseUrl() {
        return Mage::getBaseUrl("link", true);
    }
}
