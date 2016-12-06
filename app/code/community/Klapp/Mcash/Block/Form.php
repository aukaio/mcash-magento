<?php
class Klapp_Mcash_Block_Form extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('mcash/form.phtml');
	}

}