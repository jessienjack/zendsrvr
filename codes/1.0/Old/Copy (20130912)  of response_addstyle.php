<?php

//-------------------------------------------------------------------------------
// Send Acknowledgement to ecommerce Magento system

//Paul Foster
//August 2013

/*******

Recived response is in format:

{
	"method":"call",
	"params":["ProductsResponse",
			  "appUser", 
			  "appPass",  	
			  "TransactionId",
			   [
					"success",
					[
						{"sku":"737433515312"},
						{"sku":"737433515313"}
					],
					"errors", 
					[				
						{"sku":"737433515399","message":"error message"}
					]	
				]
			]	
}			

When decoded into json object we have:

Row1:	$row['method'] = 'call
Row2:	Array of "params":
	element 0: ProductsResponse
	element 1: AS400 user name (HKECUPD) - only used for authentication, if bad then send error 404 page not found
	element 2: AS400 password
	element 3: TransactionId
	element 4: data array - includes "success" and "errors: 
		element1: "success: 
		element2: array of SKUs updated successfully
			row['sku']
		element3: "errors" 
		element4: array of SKUs failed to update
			row['sku']
			row['message']

*/

//------------------------------------------------------------------------------

// Set up HTTP Header first ...
print "Status: 200\n Content-type: text/html\n\n";

include ('functions.php');

// json file received from Magento in Web Service at http://as400.griretail.com:1090/api/
// content passed here as a parameter
// Note: parm(0), first parm, is php file name so we need 2nd parm [1]

$jsonfile = $_SERVER['argv'][1];
$json = '';

// Open file on IFS.  If "false" returned then error opening file ...
$file_handler 	= fopen(trim($jsonfile),"r");
if ($file_handler == false)	
{
	$msg = trim($jsonfile) . "-> Error opening file";
	(MessageHandler($msg));
} 
 
// Read file then close it...
while (!feof($file_handler)) 
{
	$json = trim($json) . urldecode(trim(fgets($file_handler)));
}


// Decode json into array ...   
$jsonArray = json_decode($json, true);
if ($jsonArray == null)
{
	//if json parse failed (ie, bad json) write a log
	$msg =  "bad json";
	MessageHandler($msg);
	$response = array(
		'data' => '',
		'errors' => array(
			'code' => '0001',
			'message' => 'bad json received',
		),
	);
	JsonResponse($response);
	return;
	
} else {
	// json is well formed, prepare response array
	$response = array(
		'data' => '',
		'errors' => array(
		),
	);
}

// json is well formed so process it ...

$count=0;
foreach ($jsonArray as $row) 
{
	// first element (0) is method:
	if ($count == 0)
	{
		$method = $row;
		if ($row != 'call')
		{
			$msg = trim($jsonfile) . '-> Invalid Method Call in json string';
			MessageHandler($msg);
		}	
	}	
	
	// second element is data ProductsResponse), including user, pwd, trransactionId, success, fails
	if ($count == 1)
	{
	
		var_dump($row);
		echo "</br>";	
		$parm = $row[0];
		echo $parm;		
		echo "</br>";	
		$parm = $row[1];
		echo $parm;		
		echo "</br>";	
		$parm = $row[2];
		echo $parm;		
		echo "</br>";	
		$parm = $row[3];
		echo $parm;		
		echo "</br>";	
		$parm = $row[4][0];
		echo $parm;		
		echo "</br><br>";	
		$arraydata[] = $row[4];
		echo "arraydata: ";			
		var_dump($arraydata);
		echo "</br><br>";			

		foreach ( $arraydata as $rowdata) 
		{
			echo "rowdata: ";			
			var_dump($rowdata);
			echo "</br></br>";
			
			echo "rowdata[0]: ";			
			$parm = $rowdata[0];
			var_dump($parm);		
			echo "</br>";			

			echo "rowdata[1]: ";			
			$parm = $rowdata[1];
			var_dump($parm);		
			echo "</br>";			
			
			echo "rowdata[2]: ";			
			$parm = $rowdata[2];
			var_dump($parm);		
			echo "</br>";			
			
			echo "rowdata[3]: ";			
			$parm = $rowdata[3];
			var_dump($parm);		
			echo "</br></br>";			
			
			if ($rowdata[0] == 'success')
			{
				echo "success:</br>";			
				$array = $rowdata[1];				
				foreach ($array as $rowsuccess)
				{
					$parm = $rowsuccess['sku'];
					echo $parm . ' ';;
				}
			}
			echo "</br></br>";			

			if ($rowdata[2] == 'errors')
			{
				echo "errors:</br>";			
				$array = $rowdata[3];				
				foreach ($array as $rowerrors)
				{
					$parm = $rowerrors['sku'];
					$parm1= $rowerrors['message'];
					echo $parm . ' ' . $parm1 . '    ';
				}
			}
		}
	}
	
	$count++;
}	

//$jsonresponse = json_encode($response);
//echo $jsonresponse;
	
fclose($file_handler);
return;

//------------------------------------------------------------------------------
function JsonResponse($response)
//-------------------------------------------------------------------------------------
{
	$jsonresponse = json_encode($response);
	echo $jsonresponse;
	return;
}

?>

