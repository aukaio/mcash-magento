<?php
	
class Klapp_Mcash_Adminhtml_GeneratekeyController extends Mage_Adminhtml_Controller_Action {

    /**
     * Return some checking result
     *
     * @return void
     */
     public function generateAction()
     {

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
	
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('pubkey' => $keys['pubKey'], 'privkey' => $keys['privKey'])));
     }	
	
}