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


//-------------------------------------------------------------------------------------
function ProcessNewOrder($json, $liblist)
//-------------------------------------------------------------------------------------
{
	
    // signon using generic user, $conProperty set up CCSID to use UTF-8, $liblist sets up library list
	
	$user     = 'DAILYSALES';
	$password = 'UPLOAD';
	$conn = Login400($user, $password, $liblist);
	

	// At this point we can process the order array
	// Array format is as below, we need 2nd row onwards ...

	/* ------------------------------
	array(1) { <------- $row0
	[0]=>
	array(35) {   <------- $row
		["orderId"]=>
		string(6) "123456"
		["createTime"]=>
		string(14) "20131223142747"
		["type"]=>
		string(4) "sale" .... etc
	--------------------------------*/	
	
	//set up response array ...
	$orderArray = array();
	$orderCount = 0;
	
	foreach ($json as $row0) 
	{
	
		foreach ($row0 as $row) 
		{
			$orderId = $row['orderId'];
			
			if ($orderId <> null)		
			{
				$createTime    		= $row['createTime'];
				$type				= $row['type'];
				$parentOrderId 		= $row['parentOrderId'];
				$parentRmaId   		= $row['parentRmaId'];
				$status        		= $row['status'];
				$customerId    		= $row['customerId'];
				$customerName  		= $row['customerName'];
				$customerEmail 		= $row['customerEmail'];
				$customerGroup 		= $row['customerGroup'];
				$currency      		= $row['currency'];
				$subtotal      		= $row['subtotal'];
				$baseSubtotal  		= $row['baseSubtotal'];
				$discountAmount		= $row['discountAmount'];
				$baseDiscountAmount	= $row['baseDiscountAmount'];
				$shippingAmount		= $row['shippingAmount'];
				$baseShippingAmount	= $row['baseShippingAmount'];
				$grandTotal    		= $row['grandTotal'];
				$baseGrandTotal    	= $row['baseGrandTotal'];
				$totalPaid     		= $row['totalPaid'];
				$baseTotalPaid     	= $row['baseTotalPaid'];
				$itemCount     		= $row['itemCounts'];			
				$totalQty      		= $row['totalQty'];				
				$weight        		= $row['weight'];
				$paymentMethod 		= $row['paymentMethod'];
				$paymentTransactionId = $row['paymentTransactionId'];
				$payTime      		= $row['payTime'];
				$recipient     		= $row['recipient'];
				$shippingMethod		= $row['shippingMethod'];
				$shippingCountry	= $row['country'];
				$shippingState      = $row['state'];
				$shippingCity   	= $row['city'];
				$shippingStreetAddress = $row['address'];
				$shippingPostcode	= $row['postcode'];
				$telephone     		= $row['telephone'];
				$mobile        		= $row['mobile'];
				$remarks       		= $row['remarks'];
				
				
			// #gri# is used as a comma delimiter so change back to comma for db update
				$delim = '#gri#';
				$comma = ', ';
				$shippingCountry       = preg_replace("/$delim/", $comma, $shippingCountry);
				$shippingState         = preg_replace("/$delim/", $comma, $shippingState);
				$shippingCity          = preg_replace("/$delim/", $comma, $shippingCity);
				$shippingStreetAddress = preg_replace("/$delim/", $comma, $shippingStreetAddress);
				$shippingPostcode      = preg_replace("/$delim/", $comma, $shippingPostcode);
				
			
				// Insert in RECORHP for RPG to process ...
				if ($conn)
				{
					// check for null Customer Id ...
					if ($customerId == null)  
					{
						$error = $orderId . ' has null customerId';
						MessageHandler($error);
					} else {
						
						if ($createTime 		== null) $createTime = 0;
						if ($type 				== null) $type = ' ';
						if ($parentOrderId 		== null) $parentOrderId = 0;
						if ($parentRmaId   		== null) $parentRmaId = 0;
						if ($status         	== null) $status = ' ';
						if ($customerId    		== null) $customerId = 0;
						if ($customerName   	== null) $customerName = ' ';
						if ($customerEmail  	== null) $customerEmail = ' ';
						if ($customerGroup  	== null) $customerGroup = ' ';
						if ($currency 			== null) $currency = ' ';
						if ($subtotal       	== null) $subtotal = 0;
						if ($baseSubtotal   	== null) $baseSubtotal = 0;
						if ($discountAmount 	== null) $discountAmount = 0;
						if ($baseDiscountAmount == null) $baseDiscountAmount = 0;
						if ($shippingAmount 	== null) $shippingAmount = 0;
						if ($baseShippingAmount == null) $baseShippingAmount = 0;
						if ($grandTotal     	== null) $grandTotal = 0;
						if ($baseGrandTotal     == null) $baseGrandTotal = 0;
						if ($totalPaid   	    == null) $totalPaid = 0;
						if ($baseTotalPaid      == null) $baseTotalPaid = 0;
						if ($itemCount      	== null) $itemCount = 0;
						if ($totalQty       	== null) $totalQty = 0;
						if ($weight         	== null) $weight = 0;
						if ($paymentMethod  	== null) $paymentMethod = ' ';
						if ($paymentTransactionId == null) $paymentTransactionId = 0;
						if ($payTime        	== null) $paymentTime = ' ';				
						if ($recipient      	== null) $recipient = ' ';				
						if ($shippingMethod 	== null) $shippingMethod = ' ';				
						if ($shippingCountry 	== null) $shippingCountry = ' ';				
						if ($shippingState   	== null) $shippingState = ' ';				
						if ($shippingCity    	== null) $shippingCity = ' ';				
						if ($shippingStreetAddress 	== null) $shippingStreetAddress = ' ';				
						if ($shippingPostcode  	== null) $shippingPostcode = ' ';				
						if ($telephone      	== null) $telephone = ' ';				
						if ($mobile         	== null) $mobile = ' ';				
						if ($remarks        	== null) $remarks = ' ';	

						
						$zero = 0;	
						// Check order does not exist already ...
						$sql = "SELECT * FROM RECORHP WHERE OHORID = $orderId";
						$result = i5_query($sql);
						if (i5_num_rows($result) > 0)
						{
							$error = "Order $orderId already processed";
							MessageHandler($error);
						} else {	

							// Write Header Record ...
						
							$sql = 'INSERT INTO RECORHP (OHORID, OHTIME, OHTYP, OHPID,  OHRMID, OHSTS,  OHCUID, OHCNAM, OHCEMA, OHCGRP, 
														 OHCUR, OHSTOT, OHBTOT, OHDIS,  OHBDIS, OHSHP,  OHBSHP, OHGTOT, OHBGTO, OHPAID, 
														 OHBPAI, OHITEM, OHQTY,  OHWHT,  OHPAYM, OHPYID, OHPYTM, OHRECP, OHSHPM, OHSCTY, 
														 OHSSTA, OHSCIT, OHSADD, OHSPCD, OHTEL,  OHMOB,  OHREM1, OHPIC)'
								. ' VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
							$req = i5_prepare($sql);

							$newRecord = array($orderId, $createTime, $type, $parentOrderId, $parentRmaId, $status, $customerId, $customerName, $customerEmail, $customerGroup,  
											   $currency,$subtotal, $baseSubtotal, $discountAmount, $baseDiscountAmount, $shippingAmount, $baseShippingAmount, $grandTotal, $baseGrandTotal, $totalPaid, $baseTotalPaid, 
											   $itemCount, $totalQty, $weight, $paymentMethod, $paymentTransactionId, $payTime, $recipient, $shippingMethod, 
											   $shippingCountry, $shippingState, $shippingCity, $shippingStreetAddress, $shippingPostcode, 
											   $telephone, $mobile, $remarks, $zero);
							$result = i5_execute($req, $newRecord);

							//Update log
							$msg = "Received New Order: $orderId";
							MessageHandler($msg);								
							
							
							//update received-orders array, this will be sent back to Magento ...
							$orderArray[$orderCount] = $orderId;
							$orderCount++;
							
							// Now we need to retrieve all unique items.  We know how many items from $itemCount ....

						//	for ($count=0; $count<$itemCount; $count++)
						//	{
						//		$item = $row['orderItems'][$count];		
						
							foreach ($row['orderItems'] as $item)
							{
								$orderItemId = $item['orderItemId'];

								if ($orderItemId <> null)
								{
									$sku 			= $item['sku'];
									$productName 	= $item['productName'];
									$price		 	= $item['price'];
									$basePrice		= $item['basePrice'];								
									$weight		 	= $item['weight'];
									$qty 		 	= $item['qty'];
									$subtotal    	= $item['subtotal'];
									$baseSubtotal  	= $item['baseSubtotal'];
									$discountAmount = $item['discountAmount'];
									$baseDiscountAmount = $item['baseDiscountAmount'];
									$rowWeight	 	= $item['rowWeight'];
									$rowTotal	 	= $item['rowTotal'];
									$baseRowTotal	= $item['baseRowTotal'];							

									// make sure no nulls ...
									if ($sku     		== null) $sku = 0;
									if ($productName	== null) $productName = ' ';
									if ($price		    == null) $price = 0;
									if ($basePrice	    == null) $basePrice = 0;
									if ($weight			== null) $weight = 0;
									if ($qty		    == null) $qty = 0;
									if ($subtotal	    == null) $subtotal = 0;
									if ($baseSubtotal   == null) $baseSubtotal = 0;
									if ($discountAmount == null) $discountAmount = 0;
									if ($baseDiscountAmount == null) $baseDiscountAmount = 0;
									if ($rowWeight      == null) $rowWeight = 0;
									if ($rowTotal       == null) $rowTotal = 0;
									if ($baseRowTotal   == null) $baseRowTotal = 0;
									
									// Write Detail Record ...
									$sql = 'INSERT INTO RECORDP (ODORID, ODITID, ODSKU, ODPNAM, ODPRC, ODBPRC, ODWHT, ODQTY, ODSTOT, ODBSTO, ODDIS, ODBDIS, ODRWHT, ODTOT, ODBTOT)' 
									. ' VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
									$req = i5_prepare($sql);
									
									$newRecord = array($orderId, $orderItemId, $sku, $productName, $price, $basePrice, $weight, $qty, $subtotal, $baseSubtotal,  
													   $discountAmount, $baseDiscountAmount, $rowWeight, $rowTotal, $baseRowTotal);
									$result = i5_execute($req, $newRecord);

									
									// Options array set up in $options ... expand and write.  Note ... we don;t know the assoc array names so we must get them
									//foreach ($options as $element=>$value)
									//{
									//	$sql = 'INSERT INTO RECOROP (OOORID, OOITID, OOOPT, OORES)' 
									//	. ' VALUES (?,?,?,?)';
									//	$req = i5_prepare($sql);
										
									//	$newRecord = array($orderId, $orderItemId, $element, $value);
									//	$result = i5_execute($req, $newRecord);
										
									//}
								}
							}	
						}	
					}
				}
			}
			
			// At this point we should have updated the AS400 tables. 
			// Now call an RPG program (EC104R) to process the order and create picking lists		
			
			if ($orderId > 0)
			{
				// Prepare parameter for AS400 program call		
				//$description = array(array("Name" => "inOrderId", "IO" =>I5_IN, "Type" => I5_TYPE_CHAR, "Length" => "10"));
				//$pgm = i5_program_prepare("EC104R", $description);
				//if (!$pgm)
				//{
					//if failed to call program write a log
				//	die(MessageHandler("EC104R prepare failed from response_actions.php, function ProcessNewOrder:, order#: $orderId"));			   
				//}	
				
				// Set parameter with OrderId and call update program ...
				//$parm = array("inOrderId"=>$orderId);			
				//$ret = i5_program_call($pgm, $parm);
				//if (!$ret)
				//{
					//if failed to call program write a log
				//	die(MessageHandler("EC104R program call failed from response_actions.php, function ProcessNewOrder:  order#: $orderId"));			   
				//}	
				
			}
		}	
	}
	
	//Send acknowledgment back to Magento ...
	$response = array('data' => $orderArray);
	JsonResponse($response);	
	
	
	if ($conn)
	{
		i5_close($conn);
	}	

}
	
//-------------------------------------------------------------------------------------
function ProcessNewRma($json, $liblist)
//-------------------------------------------------------------------------------------
{
	
    // signon using generic user, $conProperty set up CCSID to use UTF-8, $liblist sets up library list
	
	$user     = 'DAILYSALES';
	$password = 'UPLOAD';
	$conn = Login400($user, $password, $liblist);
	

	// At this point we can process the RMA array
	
	//set up Orders array ...
	$rmaArray = array();
	$rmaCount = 0;
	
	foreach ($json as $row0) 
	{
	
		foreach ($row0 as $row) 
		{
			$rmaId = $row['rmaId'];
			
			if ($rmaId <> null)		
			{
				$type 		= $row['type'];	
				$orderId	= $row['orderId'];	
				$createTime = $row['createTime'];	
				$reason     = $row['reason'];	
				
				if ($type 		== null) $type = ' ';
				if ($createTime == null) $createTime = 0;
				if ($orderId    == null) $orderId = 0;
				if ($reason     == null) $reason = ' ';
				
				// Check order does not exist already ...
				$sql = "SELECT * FROM RECRMHP WHERE ORRMID = $rmaId";
				$result = i5_query($sql);
				if (i5_num_rows($result) > 0)
				{
					$error = "RMA $rmaId already processed";
					MessageHandler($error);
				} else {					
				
					// Write RMA Header Record ...
						
					$sql = 'INSERT INTO RECRMHP (ORRMID, ORTYP, ORORID, ORREAS, ORTIME)'
							. ' VALUES (?,?,?,?,?)';
							
					$req = i5_prepare($sql);
					$newRecord = array($rmaId, $type, $orderId, $reason, $createTime);
					$result = i5_execute($req, $newRecord);
							
					//Update log
					$msg = "Received New RMA Request: $rmaId for order $orderId";
					MessageHandler($msg);								

					//update received-orders array, this will be sent back to Magento ...
					$rmaArray[$rmaCount] = $rmaId;
					$rmaCount++;				

					
					//Now create RMA details by item ...
					foreach ($row['rmaItems'] as $item)
					{
						$rmaItemId 	= $item['rmaItemId'];

						if ($rmaItemId <> null)
						{
							$orderItemId 	= $item['orderItemId'];
							$sku         	= $item['sku'];
							$productName 	= $item['productName'];
							$qty         	= $item['qty'];
							$exchangeSku   	= $item['exchangeSku'];
							
							$sql = 'INSERT INTO RECRMDP (ODRMID, ODITID, ODOSKU, ODPROD, ODQTY, ODESKU)'
								. ' VALUES (?,?,?,?,?,?)';
							$req = i5_prepare($sql);

							$newRecord = array($rmaId, $orderItemId, $sku, $productName, $qty, $exchangeSku);
							$result = i5_execute($req, $newRecord);							
							
						}
					}
				}
			}	
		}
	}		
	
	//Send acknowledgment back to Magento ...
	$response = array('data' => $rmaArray);
	JsonResponse($response);	
	
	
	if ($conn)
	{
		i5_close($conn);
	}	
}


//-------------------------------------------------------------------------------------
function CancelOrder($json, $liblist)
//-------------------------------------------------------------------------------------
{

    // signon using generic user, $conProperty set up CCSID to use UTF-8, $liblist sets up library list
	
	$user     = 'DAILYSALES';
	$password = 'UPLOAD';
	$conn = Login400($user, $password, $liblist);
	
	//set up Orders array ...
	$orderArray = array();
	$orderCount = 0;
	
	foreach ($json as $row0) 
	{

		foreach ($row0 as $row) 
		{
			
			$orderId = $row['orderId'];
			
			if ($orderId <> null)		
			{
			
				$reason	= $row['reason'];
				
				if ($reason == null) $reason = ' ';

				$currentStatus = '';
				// Check cancelled order exists ...
				$sql = "SELECT OHSTS FROM RECORHP WHERE OHORID = $orderId fetch first 1 rows only";
				$query = i5_query($sql);
				

				//$query should now hold array from SELECT, fetch record ...
				$sqlreturn = i5_fetch_row($query, I5_READ_NEXT);			
				
				//if $sqlretun is false then order does not exist in RECORHP ...
			    if(!$sqlreturn) 
				{
					$error = "Order $orderId does not exist";
					MessageHandler($error);
				} else {	
				
				
					//Only one item in array which is OHSTS at element 0
					$currentStatus = $sqlreturn[0];
					
					if ($currentStatus == 'cancelled')
					{
						$error = "Order $orderId already cancelled";
						MessageHandler($error);
					} else {	
						// Update Header Record ...
						$status = 'cancelled';	
						$systemTime = date('YmdHis');	

						$sql = "UPDATE RECORHP SET OHSTS = " . "'" . $status . "'" . " WHERE OHORID = $orderId";
						$req = i5_prepare($sql);
						$result = i5_execute($req);

						//create new Cancel record
						$sql = 'INSERT INTO RECORCP (OCORID, OCREAS, OCTIME) VALUES (?,?,?)';
						$req = i5_prepare($sql);

						$newRecord = array($orderId, $reason, $systemTime);
						$result = i5_execute($req, $newRecord);

						//Update log
						$msg = "Cancelled Order Received: $orderId";
						MessageHandler($msg);								
						
						//update received-orders array, this will be sent back to Magento ...
						$orderArray[$orderCount] = $orderId;
						$orderCount++;			
					}				
				}	
			}	
		}
	}
	
	
	//Send acknowledgment back to Magento ...
	$response = array('data' => $orderArray);
	JsonResponse($response);	
	
	
	if ($conn)
	{
		i5_close($conn);
	}	
	
}	

?>


