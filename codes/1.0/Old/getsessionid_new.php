<?php

//-------------------------------------------------------------------------------
// Get Session ID and return to calling program

//Paul Foster
//August 2013

//------------------------------------------------------------------------------
error_reporting(E_ALL|E_STRICT);
include ('/ecommerce/functions.php');


// GetSessionId in functions.php
$sessionid = GetSessionId();

echo $sessionid;
return $sessionid;

?>