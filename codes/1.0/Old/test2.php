<?php

//-------------------------------------------------------------------------------
// Send Acknowledgement to ecommerce

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

include ("functions.php");

// json file received from Magento in Web Service at http://as400.griretail.com:1090/api/
// content passed here as a parameter
// Note: parm(0), first parm, is php file name so we need 2nd parm [1]

//$jsonfile 		= $_SERVER['argv'][1];

$jsonfile = "/home/ecommerce/process/sty_20130903114254.json";

// Open file on IFS.  If "false" returned then error opening file ...
$file_handler 	= fopen(trim($jsonfile),"r");
if ($file_handler == false)	
{
	$msg = trim($jsonfile) . ": Error opening file";
	die(MessageHandler($msg));
} 


// Read file then close it...
while (!feof($file_handler)) 
{
	$json = trim($json) . urldecode(trim(fgets($file_handler)));
}
fclose($file_handler);


//$json = '{"element1":"data1"}';

$url = 'http://as400.griretail.com:1090/api/' . $json;

$ch = curl_init($url);                                                                      
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
   'Content-Type: application/json',                                                                                
   'Content-Length: ' . strlen($json))                                                                       
);

$result = curl_exec($ch); 

echo $result;
echo "</br>";

// Decode json into array, handle if "false" returned  ...   
$jsonObject = json_decode($result, true);
if ($jsonObject == false)	
{
	echo "false json_decode";
} else {
	echo "json_decode good";
}

return;



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



