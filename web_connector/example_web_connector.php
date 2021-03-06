<?php

/**
 * Example QuickBooks SOAP Server / Web Service
 * 
 * This is an example Web Service which adds customers to QuickBooks desktop 
 * editions via the QuickBooks Web Connector. 
 * 
 * MAKE SURE YOU READ OUR QUICK-START GUIDE:
 * 	http://wiki.consolibyte.com/wiki/doku.php/quickbooks_integration_php_consolibyte_webconnector_quickstart
 * 	http://wiki.consolibyte.com/wiki/doku.php/quickbooks
 * 
 * You should copy this file and use this file as a reference for when you are 
 * creating your own Web Service to add, modify, query, or delete data from 
 * desktop versions of QuickBooks software. 
 * 
 * The basic idea behind this method of integration with QuickBooks desktop 
 * editions is to host this web service on your server and have the QuickBooks 
 * Web Connector connect to it and pass messages to QuickBooks. So, every time 
 * that an action occurs on your website which you wish to communicate to 
 * QuickBooks, you'll queue up a request (shown below, using the 
 * QuickBooks_Queue class). 
 * 
 * You'll write request handlers which generate qbXML requests for each type of 
 * action you queue up. Those qbXML requests will be passed by the Web 
 * Connector to QuickBooks, which will then process the requests and send back 
 * the responses. Your response handler will then process the response (you'll 
 * probably want to at least store the returned ListID or TxnID of anything you 
 * create within QuickBooks) and this pattern will continue until there are no 
 * more requests in the queue for QuickBooks to process. 
 * 
 * @author Keith Palmer <keith@consolibyte.com>
 * 
 * @package QuickBooks
 * @subpackage Documentation
 */

// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// We need to make sure the correct timezone is set, or some PHP installations will complain
if (function_exists('date_default_timezone_set'))
{
	// * MAKE SURE YOU SET THIS TO THE CORRECT TIMEZONE! *
	// List of valid timezones is here: http://us3.php.net/manual/en/timezones.php
	date_default_timezone_set('America/New_York');
}

// There are some constants you can define to override some default... 
//define('QUICKBOOKS_DRIVER_SQL_MYSQL_PREFIX', 'myqb_');
//define('QUICKBOOKS_DRIVER_SQL_MYSQLI_PREFIX', 'myqb_');

// If you're having trouble with performance or memory usage, you can tell the 
//	framework to only include certain chunks of itself:
//require_once 'QuickBooks/Frameworks.php';
//define('QUICKBOOKS_FRAMEWORKS', QUICKBOOKS_FRAMEWORK_WEBCONNECTOR);

// Require the framework
require_once '../QuickBooks.php';


// A username and password you'll use in: 
//	a) Your .QWC file
//	b) The Web Connector
//	c) The QuickBooks framework
//
// 	NOTE: This has *no relationship* with QuickBooks usernames, Windows usernames, etc. 
// 		It is *only* used for the Web Connector and SOAP server! 
$user = 'quickbooks';
$pass = 'password';

if (isset($_POST['customer']))
{
	$map = array(
		QUICKBOOKS_ADD_CUSTOMER => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ),
		//QUICKBOOKS_ADD_SALESRECEIPT => array( '_quickbooks_salesreceipt_add_request', '_quickbooks_salesreceipt_add_response' ), 
		//'*' => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ), 
		// ... more action handlers here ...
		);
	
	// This is entirely optional, use it to trigger actions when an error is returned by QuickBooks
	$errmap = array(
		//3070 => '_quickbooks_error_stringtoolong',				// Whenever a string is too long to fit in a field, call this function: _quickbooks_error_stringtolong()
		 'CustomerAdd' => '_quickbooks_error_customeradd', 	// Whenever an error occurs while trying to perform an 'AddCustomer' action, call this function: _quickbooks_error_customeradd()
		// '*' => '_quickbooks_error_catchall', 				// Using a key value of '*' will catch any errors which were not caught by another error handler
		// ... more error handlers here ...
		);
	
	// An array of callback hooks
	$hooks = array(
		// There are many hooks defined which allow you to run your own functions/methods when certain events happen within the framework
		// QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess', 	// Run this function whenever a successful login occurs
		);
	
	/*
	function _quickbooks_hook_loginsuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
	{
		// Do something whenever a successful login occurs...
	}
	*/
	
	// Logging level
	//$log_level = QUICKBOOKS_LOG_NORMAL;
	//$log_level = QUICKBOOKS_LOG_VERBOSE;
	$log_level = QUICKBOOKS_LOG_DEBUG;				
	//$log_level = QUICKBOOKS_LOG_DEVELOP;		// Use this level until you're sure everything works!!!
	
	// What SOAP server you're using 
	//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
	$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)
	
	$soap_options = array(		// See http://www.php.net/soap
		);
	
	$handler_options = array(
		//'authenticate' => ' *** YOU DO NOT NEED TO PROVIDE THIS CONFIGURATION VARIABLE TO USE THE DEFAULT AUTHENTICATION METHOD FOR THE DRIVER YOU'RE USING (I.E.: MYSQL) *** '
		//'authenticate' => 'your_function_name_here', 
		//'authenticate' => array( 'YourClassName', 'YourStaticMethod' ),
		'deny_concurrent_logins' => false, 
		'deny_reallyfast_logins' => false, 
		);		// See the comments in the QuickBooks/Server/Handlers.php file
	
	$driver_options = array(		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
		//'max_log_history' => 1024,	// Limit the number of quickbooks_log entries to 1024
		//'max_queue_history' => 64, 	// Limit the number of *successfully processed* quickbooks_queue entries to 64
		);
	
	$callback_options = array(
		);
	
	
	// Oooh, here's a new customer, let's do some stuff with them
	
	// Insert into our local MySQL database
	//mysql_query("INSERT INTO my_customer_table ( name, phone, email ) VALUES ( '" . $_POST['customer']['name'] . "', '" . $_POST['customer']['phone'] . "', '" . $_POST['customer']['email'] . "' ) ");
	//$id_value = mysql_insert_id();
	
	$dsn = 'mysqli://root:@localhost/quickbooks_sqli';

	$id_value = 2005;

	$Queue = new QuickBooks_WebConnector_Queue($dsn);
	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $id_value);
	
	// QuickBooks queueing class
	//$Queue = new QuickBooks_WebConnector_Queue('mysql://root:@localhost/quickbooks_sqli');
	// Queue it up!
	//$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $id_value);

	$qbxml = '<?xml version="1.0" encoding="utf-8"?>
					<?qbxml version="2.0"?>
					<QBXML>
						<QBXMLMsgsRq onError="stopOnError">
							<CustomerAddRq requestID="' . $id_value . '">
								<CustomerAdd>
									<Name>Codaemon, LLC</Name>
									<CompanyName>Codaemon, LLC</CompanyName>
									<FirstName>Keith</FirstName>
									<LastName>Palmer</LastName>
									<BillAddress>
										<Addr1>Codaemon, LLC</Addr1>
										<Addr2>134 Stonemill Road</Addr2>
										<City>Mansfield</City>
										<State>CT</State>
										<PostalCode>06268</PostalCode>
										<Country>United States</Country>
									</BillAddress>
									<Phone>860-634-1602</Phone>
									<AltPhone>860-429-0021</AltPhone>
									<Fax>860-429-5183</Fax>
									<Email>Keith@ConsoliBYTE.com</Email>
									<Contact>Keith Palmer</Contact>
								</CustomerAdd>
							</CustomerAddRq>
						</QBXMLMsgsRq>
					</QBXML>';
			
	return $qbxml;
}

function _quickbooks_customer_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	// You'd probably do some database access here to pull the record with 
	//	ID = $ID from your database and build a request to add that particular 
	//	customer to QuickBooks. 
	//	
	// So, when you implement this for your business, you'd probably do 
	//	something like this...: 
	
	/*
	// Fetch your customer record from your database
	$record = mysql_fetch_array(mysql_query("SELECT * FROM your_customer_table WHERE your_customer_ID_field = " . (int) $ID));
	
	// Create and return a qbXML request
	*/
	
	// But we're just testing, so we'll just use a static test request:
	 
	$xml = '<?xml version="1.0" encoding="utf-8"?>
		<?qbxml version="2.0"?>
		<QBXML>
			<QBXMLMsgsRq onError="stopOnError">
				<CustomerAddRq requestID="' . $requestID . '">
					<CustomerAdd>
						<Name>Codaemon, LLC</Name>
						<CompanyName>Codaemon, LLC</CompanyName>
						<FirstName>Keith</FirstName>
						<LastName>Palmer</LastName>
						<BillAddress>
							<Addr1>Codaemon, LLC</Addr1>
							<Addr2>134 Stonemill Road</Addr2>
							<City>Mansfield</City>
							<State>CT</State>
							<PostalCode>06268</PostalCode>
							<Country>United States</Country>
						</BillAddress>
						<Phone>860-634-1602</Phone>
						<AltPhone>860-429-0021</AltPhone>
						<Fax>860-429-5183</Fax>
						<Email>Keith@ConsoliBYTE.com</Email>
						<Contact>Keith Palmer</Contact>
					</CustomerAdd>
				</CustomerAddRq>
			</QBXMLMsgsRq>
		</QBXML>';
	
	return $xml;
}

/**
 * Receive a response from QuickBooks 
 * 
 * @param string $requestID					The requestID you passed to QuickBooks previously
 * @param string $action					The action that was performed (CustomerAdd in this case)
 * @param mixed $ID							The unique identifier of the record
 * @param array $extra			
 * @param string $err						An error message, assign a valid to $err if you want to report an error
 * @param integer $last_action_time			A unix timestamp (seconds) indicating when the last action of this type was dequeued (i.e.: for CustomerAdd, the last time a customer was added, for CustomerQuery, the last time a CustomerQuery ran, etc.)
 * @param integer $last_actionident_time	A unix timestamp (seconds) indicating when the combination of this action and ident was dequeued (i.e.: when the last time a CustomerQuery with ident of get-new-customers was dequeued)
 * @param string $xml						The complete qbXML response
 * @param array $idents						An array of identifiers that are contained in the qbXML response
 * @return void
 */
function _quickbooks_customer_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	// Great, customer $ID has been added to QuickBooks with a QuickBooks 
	//	ListID value of: $idents['ListID']
	// 
	// We probably want to store that ListID in our database, so we can use it 
	//	later. (You'll need to refer to the customer by either ListID or Name 
	//	in other requests, say, to update the customer or to add an invoice for 
	//	the customer. 
	
	/*
	mysql_query("UPDATE your_customer_table SET quickbooks_listid = '" . mysql_escape_string($idents['ListID']) . "' WHERE your_customer_ID_field = " . (int) $ID);
	*/
}