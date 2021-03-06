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
		var_dump(i5_error());
		return false;
	}
	
}

//-------------------------------------------------------------------------------------
function Login400Default($liblist)
//-------------------------------------------------------------------------------------
{

	$dbname = "127.0.0.1";
	$user 	= 'DAILYSALES';
	$pwd 	= 'UPLOAD';
	
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
function MessageHandler($msg)
//-------------------------------------------------------------------------------------
{
	// Write error log to /home/ECOMMERCE folder

	date_default_timezone_set("Asia/Hong_Kong");
	
    $message   = $msg;
    $timestamp = date('d/m/Y H:i:s');
	$today 	   = date('Ymd');
	$time      = date('His');
	
	$logdir  = '/home/ECOMMERCE/logs/';
	
	global $conn;
	
	//Get Log folder from resource file ...
	if ($conn != false)
	{
		$sql = "SELECT RSVAL FROM RECRSCP WHERE RSID = 'LOGFILE   ' FETCH FIRST 1 ROWS ONLY";
		$query = i5_query($sql);
		if (i5_num_rows($query) > 0)
		{	
			$resultarray = i5_fetch_row($query, I5_READ_FIRST);
			$logdir      = trim($resultarray[0]);
		} else {	
			$logdir  = '/home/ECOMMERCE/logs/';
		}	
    }

	
	$log_file  = $logdir . 'log_' . $today . '.log';
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
		/*$len = (int)strlen($lib)/10 + 1;		// get number of libraries in list
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
		}*/
		
		/*Assume there is no space in the library name itself, we can look for \s{1,} to replace all spaces between libraries */
		$libs = preg_replace("/\s+/",',',$lib);
		$liblist = preg_replace("/,$/","",$libs);
	}	
	
	return $liblist;
}
	
?>

