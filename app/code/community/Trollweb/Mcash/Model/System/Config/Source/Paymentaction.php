<?php

class Trollweb_Mcash_Model_System_Config_Source_Paymentaction
{
  public function toOptionArray()
  {
        return array( 
              array('value' => 'auth', 'label' => 'Authorize Only'),
              array('value' => 'sale', 'label' => 'Authorize + Capture')
                );
  }
}