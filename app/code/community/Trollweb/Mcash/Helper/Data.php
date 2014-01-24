<?php

class Trollweb_Mcash_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MCASH_PUB_KEY_PROD = 'TODO';
    const MCASH_PUB_KEY_TEST = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8Pg5kMWZzX0U+ZGts6Ws
oLrI1bN5PjXzFRAPza19qYrONVxhFlJx8AQWohISL1hKVPJCMuyQKhs0/2jtWk+E
mDHXFafW+kYV7lseznj6nW49VFyxHYdQDNHgpyUA5p+lmZABbmcKGabw/Cp28vtH
im4zWBGVXnQ7UPm1peMzeuaB7L246J+ZcfLpd3trSWg2mywB23rqELzTNKi0s7cb
kS+2gk5B72q3qcaTO47rPgEVcUTB2A+jxcu6rOVFCbhQ8+JkLDPeHPDuIBQ5mAwN
XLY+3ffovc31S5cMhquiKaYYwiuxeI23AWtNV2FoD00bm4q+5XCuBGgPJf3nkNYV
eQIDAQAB
-----END PUBLIC KEY-----';


    public function getConfig($field,$storeId=null)
    {
        return Mage::getStoreConfig('payment/mcash/'.$field,$storeId);
    }

    public function isProduction() {
        return !$this->getConfig('test');
    }

    public function getMcashPublicKey() {
        return $this->isProduction() ? self::MCASH_PUB_KEY_PROD : self::MCASH_PUB_KEY_TEST;
    }
}
