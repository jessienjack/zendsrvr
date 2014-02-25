<?php



//-------------------------------------------------------------------------------
// 	General Functions for interfacing with ecommerce system

//	Paul Foster
//	August 2013

//------------------------------------------------------------------------------


//-------------------------------------------------------------------------------------
function Login400($user, $pwd, $liblist)
//-------------------------------------------------------------------------------------
{

	$dbname   = "127.0.0.1";
	
	if ($liblist == null)
	{
		$conProperty = array(I5_OPTIONS_JOBNAME=>"ECOMORDER", I5_OPTIONS_LOCALCP=>"UTF-8;ISO8859-1");
	} else {
		$conProperty = array(I5_OPTIONS_JOBNAME=>"ECOMORDER", I5_OPTIONS_LOCALCP=>"UTF-8;ISO8859-1", I5_OPTIONS_INITLIBL=>$liblist);
    }
	
	$conn = i5_connect($dbname, $user, $pwd, $conProperty);	

	if ($conn)  {
		return $conn;
	} else {	
		return false;
	}
	
}

//-------------------------------------------------------------------------------------
function GetSessionId()
//-------------------------------------------------------------------------------------
{

// Variables for web service ...

$loginid = GetLoginID();
$apikey  = GetAPIKey();
$url 	 = GetURL();

$json = "{" . '"method":"login",' . '"params":["' . $loginid . '", "' . $apikey . '"]}';

// Connect to web service using cURL ...
$result = WebServiceConnect($url, $json);

// Decode json into array ...   
$jsonObject = json_decode($result);

// Check for errors ...
if ($jsonObject == null)
{
	//if json parse failed (ie, bad json) write a log
	die(MessageHandler("Bad json returned for session id"));		  
}

if ($jsonObject->{'errors'} <> null) 
{
	//if json parse failed (ie, bad json) write a log
	die(MessageHandler("Errors returned, unsuccessful session id"));		  
}

if ($jsonObject->{'data'} == null)
{
	//if json parse failed (ie, bad json) write a log
	die(MessageHandler("Bad json returned for session id"));		  
}

// Success, we have a session ID.  Return it to calling program
$sessionid = $jsonObject->{'data'};

return $sessionid;

}

//-------------------------------------------------------------------------------------
function WebServiceConnect($url, $json)
//-------------------------------------------------------------------------------------
{

$ch = curl_init($url);                                                                      
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
   'Content-Type: application/json',                                                                                
   'Content-Length: ' . strlen($json))                                                                       
);
$result = curl_exec($ch); 
return $result;
}




//-------------------------------------------------------------------------------------
function GetURL()
//-------------------------------------------------------------------------------------
{
	$url = "http://beta.centralcentralshop.com/api/hkAs400/rest/";
	return $url;
}

//-------------------------------------------------------------------------------------
function GetLoginID()
//-------------------------------------------------------------------------------------
{
	$loginid = "hkas400";
	return $loginid;
}

//-------------------------------------------------------------------------------------
function GetAPIKey()
//-------------------------------------------------------------------------------------
{
	$apikey = "db1d2e160e6f3a9748fd0e69ef56c2c5";
	return $apikey;
}

//-------------------------------------------------------------------------------------
function MessageHandler($msg)
//-------------------------------------------------------------------------------------
{
	// Write error log to /home/ECOMMERCE folder
	
	date_default_timezone_set("Asia/Hong_Kong");
	
    $message   = $msg;
    $timestamp = date('d/m/Y H:i:s');
	$today 	   = date('Ymd');
	$time      = date('His');
    $log_file  = '/home/ECOMMERCE/logs/err_' . $today . '.log';
	 
    error_log('['.$timestamp.'] INFO: '.$message.PHP_EOL, 3, $log_file);
}

//------------------------------------------------------------------------------
function JsonResponse($response)
//-------------------------------------------------------------------------------------
{
	$jsonresponse = json_encode($response);
	echo $jsonresponse;
	return;
}

//------------------------------------------------------------------------------
function LibraryList($lib)
//-------------------------------------------------------------------------------------
{
	$liblist = ' ';
	$pos = 0;
	
	if ($lib <> ' ')
	{
		$len = (int)strlen($lib)/10 + 1;		// get number of libraries in list
		for ($i=0; $i<=$len; $i++)
		{
			$nextlib = trim(substr($lib,$pos,10));
			if ($nextlib > ' ')
			{	
				if ($i > 0)
				{
					$liblist = trim($liblist) . ',';
				}
				$liblist = trim($liblist) . '"' . trim($nextlib) . '"';
			}
			$pos += 11;  //$pos is character position in $lib string.  10 chars per library plus space separator
		}
		return $liblist;
	}	
}
	
?>

