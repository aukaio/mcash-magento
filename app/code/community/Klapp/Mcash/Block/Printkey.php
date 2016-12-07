<?php

class Klapp_Mcash_Block_Printkey extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    // this will look in adminhtml theme
    protected $_template = 'mcash/pubkey.phtml'; 

    public function render(Varien_Data_Form_Element_Abstract $element)
    {	       
        return $this->toHtml();
    }
}