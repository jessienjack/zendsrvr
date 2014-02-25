<?php

//-------------------------------------------------------------------------------
// Send Inventory by SKU to ecommerce webservice

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

// Variables for web service ...
$url 		= "http://beta.centralcentralshop.com/api/hkAs400/rest/";
$loginid 	= "hkas400";
$apikey  	= "db1d2e160e6f3a9748fd0e69ef56c2c5";

$json = "{" . '"method":"login",' . '"params":["' . $loginid . '", "' . $apikey . '"]}';

// Connect to web service using cURL ...
$ch = curl_init($url);                                                                      
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
   'Content-Type: application/json',                                                                                
   'Content-Length: ' . strlen($json))                                                                       
);                                                                                                                   
 
$result = curl_exec($ch);

// Decode json into array ...   
$jsonObject = json_decode($result);
$sessionid  = $jsonObject->{'data'};



//$json = $_SERVER['argv'][1];  // json string passed as parameter

$url  = "http://beta.centralcentralshop.com/api/hkAs400/rest/";
$jsondata = '{"method":"call","params":["' . $sessionid . 
	'","newProducts",
	{"transaction_id": "12121212121",
	"products":[
		{"sku": "NWBERTA-01-5","attribute_set": "Shoes","product_name": "BERTA","brand": "Nine West","style_name": "NWBERTA","style_no": "301033959L","description": "Your wardrobe fall / Winter essentials", 
		 "qty": "10","price": "1680","season": "Fall 2013","country_group": "All","color_code": "01","color_label": "BLACK"}]}]}'; 



echo 'sent json string is: ' . $jsondata . "</br>";

if ($jsondata <> null)
{
	// Connect to web service using cURL ...
	$ch = curl_init($url);                                                                      
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);                                                                  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		'Content-Type: application/json',                                                                                
		'Content-Length: ' . strlen($jsondata))                                                                       
		);                                                                                                                   
 
$result = curl_exec($ch);
echo "</br>Returned json is: " . $result;

// Decode json into array ...   
//$jsonObject = json_decode($result);

// Check for errors ...
//if ($jsonObject == null)
//{
	//if json parse failed (ie, bad json) write a log
//	die(ErrorHandler("Bad json returned for session id"));		  
//}

//if ($jsonObject->{'errors'} <> null) 
//{
	//if json parse failed (ie, bad json) write a log
//	die(ErrorHandler("Errors returned, unsuccessful session id"));		  
}



//-------------------------------------------------------------------------------------
function ErrorHandler($msg)
//-------------------------------------------------------------------------------------
{
	// Write error log to /home/ECOMMERCE folder
    $message   = $msg;
    $timestamp = date('d/m/Y H:i:s');
	$today 	   = date('Ymd');
	$time      = date('His');
    $log_file  = '/home/ECOMMERCE/log/err_' . $today . '.log';
	 
    error_log('['.$timestamp.'] INFO: '.$message.PHP_EOL, 3, $log_file);
	

	// Also write a record to RECERRP ...
	$sql = 'INSERT INTO RECERRP (ERPGM, ERUSR, ERTYP, ERDAT, ERTIM, ERORID, ERERR)' 
	. ' VALUES (?,?,?,?,?,?,?)';	
	$req = i5_prepare($sql);
	
	$newRecord = array("getsessionid", "PHP", $user, $today, $time, " ", " ", " ", " ", "0", $msg);

	$result = i5_execute($req, $newRecord);

}


?>



