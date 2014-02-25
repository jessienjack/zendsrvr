<?php

//-------------------------------------------------------------------------------
// Send Style Master to ecommerce

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

include ("functions.php");

// json file created in RPGLE and passed as a parameter.
// Note: parm(0), first parm, is php file name so we need 2nd parm [1]


$jsonfile 		= $_SERVER['argv'][1];
$url    		= $_SERVER['argv'][2];

// Open file on IFS.  If "false" returned then error opening file ...
$file_handler 	= fopen(trim($jsonfile),"r");
if ($file_handler != true)	
{
	$msg = trim($jsonfile) . ": Error opening file";
	die(MessageHandler($msg));
} else {
    if ($url == null)	
	{
		$msg = trim($jsonfile) . ": Error in API Path: $url ";
		die(MessageHandler($msg));
	} else {
		$msg = trim($jsonfile) . ": Processing ...";
		MessageHandler($msg);
	}	
}


$msg = trim($jsonfile) . ": url: $url";
MessageHandler($msg);


$json = '';
// Read file then close it...
while (!feof($file_handler)) 
{
	$json = trim($json) . trim(fgets($file_handler));
}
fclose($file_handler);


// Post json to web service, WebServiceConnect is in "functions.php"...
if ($json <> null)
{
	$result = WebServiceConnect($url, $json); 
}

// Decode json into array, handle if "false" returned  ...   
$jsonObject = json_decode($result, true);
if ($jsonObject == false)	
{
	$msg = trim($jsonfile) . ": Error in returned JSON";
	$vardump = var_dump($jsonObject);
	$msg = trim($jsonfile) . ": Error in returned json from Magento: $result";		
	die(MessageHandler($msg));
}


if ($result == false)	
{
	$msg = trim($jsonfile) . ": Error Connecting to Magento WebService";
	die(MessageHandler($msg));
}

foreach ($jsonObject as $name=>$value) {
// Check for success, will be returned in "data" array ...
	if ($name == 'data')
	{
		if ($value == null)
		{
			$msg = trim($jsonfile) . ": Style Update Error, see error messages";
			MessageHandler($msg);
		} else {
			$msg = trim($jsonfile) . ": Status Returned: $value";
			die(MessageHandler($msg));	
		}
	}

// Check for fail, will be returned in "errors" array ...
	if ($name == 'errors')
	{
		foreach ($value as $entry=>$value1) {
			$msg = trim($jsonfile) . ": ERROR,  message is: " . '"' . $value1['message'] . '"' . " code=" . $value1['code'];
			die(MessageHandler($msg));	
		}
		
	}
	
}

?>



