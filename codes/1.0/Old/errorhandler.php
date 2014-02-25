<?php

//-------------------------------------------------------------------------------
// Error handler routine.  Include in all PHP 

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

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
}

?>

