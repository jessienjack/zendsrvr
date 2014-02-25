<?php

//-------------------------------------------------------------------------------
// Get Session ID and return to calling program

//Paul Foster
//August 2013

//------------------------------------------------------------------------------

include ('functions.php');

// GetSessionId in functions.php
$sessionId = GetSessionId();

echo $sessionId;
return $sessionId;

?>

