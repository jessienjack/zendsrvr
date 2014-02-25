<?php

//-------------------------------------------------------------------------------
// Send Acknowledgement to ecommerce Magento system

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

include ('functions.php');

// Set up HTTP Header ...
print "Status: 200\n Content-type: text/html\n\n";


// json file received from Magento in Web Service at http://as400.griretail.com:1090/api/
// content passed here as a parameter
// Note: parm(0), first parm, is php file name so we need 2nd parm [1]

$jsonfile = $_SERVER['argv'][1];
$json = '';

// Open file on IFS.  If "false" returned then error opening file ...
$file_handler 	= fopen(trim($jsonfile),"r");
if ($file_handler == false)	
{
	$msg = trim($jsonfile) . ": Error opening file";
	//die(MessageHandler($msg));
} 
 
// Read file then close it...
while (!feof($file_handler)) 
{
	$json = trim($json) . urldecode(trim(fgets($file_handler)));
	//MessageHandler($json);
}



// Decode json into array ...   
$jsonArray = json_decode($json, true);
if ($jsonArray == null)
	{
	//if json parse failed (ie, bad json) write a log
	$msg =  "bad json";
	echo $msg;
	//MessageHandler($msg);
	return;
}

	echo $json;
	var_dump($jsonArray);	
	
fclose($file_handler);

?>



