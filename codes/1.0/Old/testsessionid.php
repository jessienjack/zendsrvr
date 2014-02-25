<?php

//-------------------------------------------------------------------------------
// Get Session ID and return to calling program

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

include ("functions.php");

$sessionid = GetSessionID();

echo $sessionid;
return $sessionid;

?>

