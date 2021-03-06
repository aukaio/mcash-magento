<?php
	
namespace mCASH;

/**
 * Merchant class.
 * 
 * @extends Resource
 */
class Merchant extends Resource {
	
	/**
	 * endpointUrl
	 * 
	 * (default value: "merchant")
	 * 
	 * @var string
	 * @access protected
	 */
	protected $endpointUrl = "merchant";    

    /**
     * retrieve function.
     * 
     * @access public
     * @static
     * @param mixed $id (default: null)
     * @param mixed $opts (default: null)
     * @return Merchant
     */
    public static function retrieve($id = null, $opts = null){
        return self::_retrieve($id, $opts);
    } 
   
	
}