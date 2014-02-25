<?php

//General Functions for interfacing with ecommerce system

// Jack Yu
// Feburary 2014

class Functions
{	
	public static function Login400($dbname='127.0.0.1', $user, $pwd, $liblist)
	{
		$conProperty = array(I5_OPTIONS_JOBNAME=>"ECOMORDER",I5_OPTIONS_LOCALCP=>"UTF-8; ISO8859-1");
		if($liblist == null)
		{
			$conProperty[I5_OPTION_INITLIBL] = $liblist);
		}
		
		$conn = i5_connect($dbname, $user, $pwd, $conProperty);
		
		if($conn == FALSE)
		{
			throw new Exception("Connect to AS400 Error : ".i5_error());
		}
		
		return $conn;
	}
	
	public static function Login400Default($liblist)
	{
		$dbname = '127.0.0.1';
		$user = 'DAILYSALES';
		$pwd = 'UPLOAD';
		return $this->Login400($dbname, $user, $pwd, $liblist);
	}
	
	public static function WebServicesConnect($url, $json)
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
	
	public static function MessageHandler($msg)
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
	
	
	public static function LibraryList($lib)
	{
		$liblist = ' ';
		$pos = 0;
		
		if ($lib <> ' ')
		{
			/*Assume there is no space in the library name itself, we can look for \s{1,} to replace all spaces between libraries */
			$libs = preg_replace("/\s+/",',',$lib);
			$liblist = preg_replace("/,$/","",$libs);
		}
		
		return $liblist;
	}
}