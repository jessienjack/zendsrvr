<?php

// If here then data is good, send response back...
$response = array('data' => 'received');

$jsonresponse = json_encode($response);
echo $jsonresponse;		

?>		