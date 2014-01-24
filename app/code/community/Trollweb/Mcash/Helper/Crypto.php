<?php

class Trollweb_Mcash_Helper_Crypto extends Mage_Core_Helper_Abstract
{
    // Sign data using RSA private key with PKCS1 v1.5 padding and SHA256 hash
    public function sign_pkcs1($key, $data) {
        $ok = openssl_sign($data, $signature, $key, "sha256");
        if (!$ok) {
            Mage::throwException('Failed to sign data');
        }
        return base64_encode($signature);
    }

    public function verify_signature_pkcs1($key, $data, $signature) {
        return openssl_verify($data, base64_decode($signature), $key, "sha256");
    }
}

