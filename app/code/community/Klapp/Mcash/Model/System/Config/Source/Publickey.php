<?php
	
class Klapp_Mcash_Model_System_Config_Source_Publickey extends Mage_Core_Model_Config_Data
{
    public function getValue()
    {
	    // Check if pub and privkeys have been generated for the plugin, if not. Generate!
	    $current_key = Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_pub_key'));
	    
	    $key_version = 6;
		$current_key_version = Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('current_key_version'));
		
		if( $current_key == false || is_null( $current_key ) || $current_key == "" || $key_version > $current_key_version ){
			
	        $res = openssl_pkey_new(array(
	        	"digest_alg" => "sha256",
	            "private_key_bits" => 1024,
	            "private_key_type" => OPENSSL_KEYTYPE_RSA,
	        ));
	        
	        openssl_pkey_export($res, $privKey);
	        $pubKey = openssl_pkey_get_details($res);
	        
	        $keys = array(
	            'pubKey'      => $pubKey['key'],
	            'privKey'     => $privKey
	        );
			//die(var_dump($keys['pubKey']));
			Mage::helper('mcash')->saveConfig('user_pub_key', $keys['pubKey']);
			Mage::helper('mcash')->saveConfig('user_priv_key', $keys['privKey']);
			Mage::helper('mcash')->saveConfig('current_key_version', $key_version);
			
		} 	
		    
        return Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_pub_key'));
        
    }
    
    public function save(){
		Mage::helper('mcash')->saveConfig('user_pub_key', Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_pub_key')));
		Mage::helper('mcash')->saveConfig('user_priv_key', Mage::helper('core')->decrypt(Mage::helper('mcash')->getConfig('user_priv_key')));   
    }

    
}