<?php

class Trollweb_Mcash_Model_System_Config_Source_Paymentaction
{
  public function toOptionArray()
  {
        return array( 
//              array('value' => 'authorize', 'label' => 'Authorize Only'),
              array('value' => 'authorize_capture', 'label' => 'Authorize + Capture')
                );
  }
}
