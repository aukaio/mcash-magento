<?php
/*
License: The MIT License (MIT)
Author: Klapp Media AS

mCASH API PHP SDK V1.0	
*/	

// The mCASH class
require_once( dirname( __FILE__ ) . '/lib/mCASH.php' );

// HTTP Client
require_once( dirname( __FILE__ ) . '/lib/HttpClient/ClientInterface.php' );
require_once( dirname( __FILE__ ) . '/lib/HttpClient/CurlClient.php' );

// Utilities
require_once( dirname( __FILE__ ) . '/lib/Utilities/Headers.php' );
require_once( dirname( __FILE__ ) . '/lib/Utilities/Utilities.php' );
require_once( dirname( __FILE__ ) . '/lib/Utilities/Encryption.php' );
 
// Error handling
require_once( dirname( __FILE__ ) . '/lib/Error/Base.php' );
require_once( dirname( __FILE__ ) . '/lib/Error/Api.php' );
require_once( dirname( __FILE__ ) . '/lib/Error/ApiConnection.php' );
require_once( dirname( __FILE__ ) . '/lib/Error/Authentication.php' );
require_once( dirname( __FILE__ ) . '/lib/Error/Request.php' );

// Components
require_once( dirname( __FILE__ ) . '/lib/mCASHObject.php' );
require_once( dirname( __FILE__ ) . '/lib/Requestor.php' );
require_once( dirname( __FILE__ ) . '/lib/Resource.php' );

// The API Resources
require_once( dirname( __FILE__ ) . '/lib/LastSettlement.php' );
require_once( dirname( __FILE__ ) . '/lib/Ledger.php' );
require_once( dirname( __FILE__ ) . '/lib/Merchant.php' );
require_once( dirname( __FILE__ ) . '/lib/PaymentRequest.php' );
require_once( dirname( __FILE__ ) . '/lib/PaymentRequestOutcome.php' );
require_once( dirname( __FILE__ ) . '/lib/PermissionRequest.php' );
require_once( dirname( __FILE__ ) . '/lib/PermissionRequestOutcome.php' );
require_once( dirname( __FILE__ ) . '/lib/Pos.php' );
require_once( dirname( __FILE__ ) . '/lib/Report.php' );
require_once( dirname( __FILE__ ) . '/lib/Settlement.php' );
require_once( dirname( __FILE__ ) . '/lib/SettlementAccount.php' );
require_once( dirname( __FILE__ ) . '/lib/Shortlink.php' );
require_once( dirname( __FILE__ ) . '/lib/StatusCode.php' );
require_once( dirname( __FILE__ ) . '/lib/Ticket.php' );
require_once( dirname( __FILE__ ) . '/lib/User.php' );

?>