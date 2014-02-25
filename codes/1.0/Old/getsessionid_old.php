<?php

//-------------------------------------------------------------------------------
// Get Session ID and return to calling program

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

// Check for errors ...
if ($jsonObject == null)
{
	//if json parse failed (ie, bad json) write a log
	die(ErrorHandler("Bad json returned for session id"));		  
}

if ($jsonObject->{'errors'} <> null) 
{
	//if json parse failed (ie, bad json) write a log
	die(ErrorHandler("Errors returned, unsuccessful session id"));		  
}

if ($jsonObject->{'data'} == null)
{
	//if json parse failed (ie, bad json) write a log
	die(ErrorHandler("Bad json returned for session id"));		  
}

// Success, we have a session ID.  Return it to callling program
$sessionid = $jsonObject->{'data'};


echo $sessionid;
return $sessionid;

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

