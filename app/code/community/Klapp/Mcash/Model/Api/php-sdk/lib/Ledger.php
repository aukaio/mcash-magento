<?php
	
namespace mCASH;

/**
 * Ledger class.
 * 
 * @extends Resource
 */
class Ledger extends Resource {
	
	protected static $updateParams = array( 'description' );
	
	/**
	 * endpointUrl
	 * 
	 * (default value: "ledger")
	 * 
	 * @var string
	 * @access protected
	 */
	protected $endpointUrl = "ledger";

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Ledger
     */
    public static function create($params = null, $opts = null){
        return self::_create($params, $opts);
    }	    

    /**
     * retrieve function.
     * 
     * @access public
     * @static
     * @param mixed $id (default: null)
     * @param mixed $opts (default: null)
     * @return Ledger
     */
    public static function retrieve($id = null, $opts = null){
        return self::_retrieve($id, $opts);
    } 

	/**
	 * update function.
	 * 
	 * @access public
	 * @return boolean
	 */
	public function update(){
		$result = $this->_save();
		return Utilities\Utilities::handleResponseCode( $result->_opts, $result->_values );				
	}
	
	/**
	 * delete function.
	 * 
	 * @access public
	 * @return boolean
	 */
	public function delete(){
		$result = $this->_delete();
		return Utilities\Utilities::handleResponseCode( $result->_opts, $result->_values );				
	}   
	
	/**
	 * report function.
	 * 
	 * @access public
	 * @return Report
	 */
	public function report(){
		$report = new Report;
		$report->setLedgerId( $this->id );
		return $report;
	}
	
	
	
}