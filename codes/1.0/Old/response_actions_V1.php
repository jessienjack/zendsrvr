<?php

//-------------------------------------------------------------------------------------
function ProductsResponse($txid, $arraydata, $jsonfile, $liblist)
//-------------------------------------------------------------------------------------
{

/*-------------------------------------------------------------------------------------

Process Response from ecommerce Magento system after Produce upload
We need to know which SKUs updated successfully/failed

Paul Foster
September 2013

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

--------------------------------------------------------------------------------------------------*/

	// User DAILYSALES to update AS400 rather than user passed in json.  json user has no authority
	$user = 'DAILYSALES';
	$pwd  = 'UPLOAD';
	$as400 = Login400($user, $pwd, $liblist);
	
	if ($as400 == false)
	{
		$msg = trim($jsonfile) . '-> AS400 DAILYSALES login failed, try restarting ZENDSVR subsystem';
		MessageHandler($msg);
			$response = array(
			'data' => '',
			'errors' => array(
				'code' => '0002',
				'message' => 'AS400 login failed',
	
			),
		);	
		JsonResponse($response);
		return;		
	}
			
	foreach ( $arraydata as $rowdata) 
	{
		if ($rowdata[0] == 'success')
		{
			$array = $rowdata[1];

			
			// If here then data is good and connected to AS400, send response back...
			$response = array('data' => 'received');
			JsonResponse($response);
			
			$rep = json_encode($response);
			$msg = trim($jsonfile) . '-> Response Sent:  ' . $rep;
			MessageHandler($msg);
			
			foreach ($array as $rowsuccess)
			{
				// update RECSNTP with "Y" for successful SKU
				$sku = $rowsuccess['sku'];
				$sql = "UPDATE RECSNTP SET ENCFM = 'Y' 
						WHERE ENSKU = $sku";
				$req = i5_prepare($sql);
				$result = i5_execute($req);

				// Set ESREDY = "N" for successful SKU, so that it doesn't get build neXt time
				$sql = "UPDATE RECSTDP SET ESREDY = ' ' 
						WHERE ESSTY IN (SELECT SBSTY FROM RSTBARP WHERE SBSKU = $sku)";
				$req = i5_prepare($sql);
				$result = i5_execute($req);
				
			}
		}	

		
		if ($rowdata[2] == 'errors')
		{
			$array = $rowdata[3];				
			foreach ($array as $rowerrors)
			{
				$sku = $rowerrors['sku'];
				$err = $rowerrors['message'];
				
				//If "already exists" message, then update RECSNTP status to Confirmed ...		
				if (strpos($err,'already exists') !== false) {
					$sql = "UPDATE RECSNTP SET ENCFM = 'Y' 
							WHERE ENSKU = $sku";
					$req = i5_prepare($sql);
					$result = i5_execute($req);
					
					// Set ESREDY = "N" for successful SKU, so that it doesn't get build next time
					$sql = "UPDATE RECSTDP SET ESREDY = ' ' 
							WHERE ESSTY IN (SELECT SBSTY FROM RSTBARP WHERE SBSKU = $sku)";
					$req = i5_prepare($sql);
					$result = i5_execute($req);
				} else {
				
					$sql = "UPDATE RECSNTP SET ENCFM = ' ' 
							WHERE ENSKU = $sku and ENTID = $txid";
					$req = i5_prepare($sql);
					$result = i5_execute($req);				
				
					$msg = trim($jsonfile) . "-> SKU $sku failed to update, message: $err";
					MessageHandler($msg);				
				}	
			}
		}
	}

return;
}

?>

