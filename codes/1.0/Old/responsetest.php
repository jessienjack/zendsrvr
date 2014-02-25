<?php

/*-------------------------------------------------------------------------------

Send Acknowledgement to ecommerce Magento system

This will process 1st array of json, and then call appropriate proessing script

Paul Foster
September 2013

json format:

{
	"method":"call",
	"params":["action",
			  "user", 
			  "pass",  	
			  "TransactionId",
			   [data]
			]	
}			

Here we process user, pass and determine which script to call from "action", passing data as parm.

//------------------------------------------------------------------------------
*/

include ('functions.php');
include ('response_actions_test.php');

// Set up HTTP Header first ...
//print "Status: 200\n Content-type: text/html\n\n";
//header('HTTP/1.1 200 OK');
//echo "here";

// json file received from Magento in Web Service at http://as400.griretail.com:1090/api/
// content passed here as a parameter
// Note: parm(0), first parm, is php file name so we need 2nd parm [1]

$jsonfile = $_SERVER['argv'][1];  // file created by RPGLE RCJSONR
$lib      = $_SERVER['argv'][2];  // current library list passed as parameter
	
$json = '';

// format $lib into library list
$liblist = LibraryList($lib);

// Open file on IFS.  If "false" returned then error opening file ...
$file_handler 	= fopen(trim($jsonfile),"r");
if ($file_handler == false)	
{
	$msg = trim($jsonfile) . "-> Error opening file";
	MessageHandler($msg);
} 
 
// Read file then close it...
while (!feof($file_handler)) 
{
	$json = trim($json) . urldecode(trim(fgets($file_handler)));
}


// Decode json into array ...   
$jsonArray = json_decode($json, true);
if ($jsonArray == null)
{
	//if json parse failed (ie, bad json) write a log
	$msg =  "bad json";
	MessageHandler($msg);
	$response = array(
		'data' => '',
		'errors' => array(
			'code' => '0001',
			'message' => 'bad json received',
		),
	);
	JsonResponse($response);
	return;
	
//} else {
	// json is well formed, prepare response array
//	$response = array(
//		'data' => '',
//		'errors' => array(
//		),
//	);

}

// json is well formed so process it ...

$count=0;
foreach ($jsonArray as $row) 
{
	// first element (0) is method, must be "call":
	if ($count == 0)
	{
		$method = $row;
		if ($row != 'call')
		{
			$msg = trim($jsonfile) . '-> Invalid Method Call in json string';
			MessageHandler($msg);
				$response = array(
				'data' => '',
				'errors' => array(
					'code' => '0002',
					'message' => 'bad method in json',
			
				),
			);	
			JsonResponse($response);
			return;
		}	
	}	
	
	// second element (1) is data, including user, pwd, transactionId, data array (success, fails)
	if ($count == 1)
	{
		$action = $row[0];
		$user = $row[1];
		$pwd  = $row[2];
		$txid = $row[3];
		$arraydata[] = $row[4];

		// login to AS400.  This account should have no AS400 authority, just authenticates that user is from Magento
		$conn = Login400($user, $pwd, $liblist);
		if ($conn == false)
		{
			$msg = trim($jsonfile) . "-> AS400 $user, $pwd login failed, try restarting ZENDSVR subsystem";
			MessageHandler($msg);
				$response = array(
				'data' => '',
				'errors' => array(
					'code' => '0002',
					'message' => 'AS400 login failed',
			
				),
			);	
			JsonResponse($response);
			return;			
		}
		
		// All fine so far so call PHP program to process data (see include files at top of this pgm) ...
		
		//Response received from Magento after sending new/updated style master ...
		if ($action == 'ProductsResponse')
		{
			ProductsResponse($txid, $arraydata, $jsonfile, $liblist);
		}

		// New orders received from Magento ...
		if ($action == 'newOrders')
		{
			ProcessNewOrder($arraydata, $liblist);
		}		
		-
		// New RMA received from Magento ...
		if ($action == 'newRmas')
		{
			ProcessNewRMA($arraydata, $liblist);
		}	
		
		// Cancel Order received from Magento ...
		if ($action == 'cancelOrders')
		{
			CancelOrder($arraydata, $liblist);
		}		
		

	}	
	
	$count++;
}
	
fclose($file_handler);
return;



?>

