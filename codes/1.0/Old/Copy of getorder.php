<?php

//-------------------------------------------------------------------------------
// This PHP is called from RPGLE (EC130R), passing the following parameters:
//		data as a json string
//		library list as a string of libraries separated by a space

// The json is parsed here into ecommerce tables on AS400
// The RPG program EC104R is then called from here to process and create picks

//Paul Foster
//April 2013

//------------------------------------------------------------------------------

	
	$json = $_SERVER['argv'][1];  // json string passed as parameter
	$lib  = $_SERVER['argv'][2];  // current library list passed as parameter
	
	//Create library list from $lib.  Parm is passed as long string of each 10-char lib + space
	// e.g. if passed library list is:  'LIB1       LIB2       LIB3       '
    // we need to change this to:  "LIB1","LIB2,"LIB3"	
	
	//public $orderId;  // make public so can be used in error function
	
	$pos = 0;			
    $liblist = ' ';

	if ($lib <> ' ')
	{
		$len = (int)strlen($lib)/10 + 1;		// get number of libraries in list
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
		}
	}	
	
	
    // signon using generic user, $conProperty set up CCSID to use UTF-8, $liblist sets up library list
	$user     = 'DAILYSALES';
	$password = 'UPLOAD';
	$dbname   = "127.0.0.1";
    $conProperty = array(I5_OPTIONS_JOBNAME=>"ECOMORDER", I5_OPTIONS_LOCALCP=>"UTF-8;ISO8859-1", I5_OPTIONS_INITLIBL=>$liblist);

	$conn = i5_connect($dbname, $user, $password, $conProperty);
	if (!$conn) 	
	{
		// if login failed write a log
		die(ErrorHandler(i5_errormsg()));
	}
  
// Decode json into array ...   
    $jsonArray = json_decode($json, true);
	if (jsonArray == null)
	{
		//if json parse failed (ie, bad json) write a log
		die(ErrorHandler("Bad json passed"));		  
	}

	
// At this point we can process the data ...
	
	foreach ($jsonArray as $row) 
	{
		$orderId = $row['orderId'];

		if ($orderId <> null)		
		{
			$createTime    = $row['createTime'];
			$status        = $row['status'];
			$customerId    = $row['customerId'];
			$customerName  = $row['customerName'];
			$customerEmail = $row['customerEmail'];
			$customerGroup = $row['customerGroup'];
			$customerGender= $row['customerGender'];
			$subTotal      = $row['subTotal'];
			$discountAmount= $row['discountAmount'];
			$shippingAmount= $row['shippingAmount'];
			$grandTotal    = $row['grandTotal'];
			$totalPaid     = $row['totalPaid'];
			$itemCount     = $row['itemCounts'];			
			$totalQty      = $row['totalQty'];				
			$weight        = $row['weight'];
			$paymentMethod = $row['paymentMethod'];
			$paymentTransactionId = $row['paymentTransactionId'];
			$payTime       = $row['payTime'];
			$recipient     = $row['recipient'];
			$shippingMethod= $row['shippingMethod'];
			$shippingAddress= $row['shippingAddress'];
			$postcode      = $row['postcode'];
			$telephone     = $row['telephone'];
			$mobile        = $row['mobile'];
			$fapiao_title  = $row['fapiao_title'];
			$fapiao_type   = $row['fapiao_type'];
			$remarks       = $row['remarks'];
			
			
		// #gri# is used as a comma delimiter so change back to comma for db update
			$delim = '#gri#';
			$comma = ', ';
			$shippingAddress = preg_replace("/$delim/", $comma, $shippingAddress);
			
			// Insert in RECORHP for RPG to process ...
			if ($conn)
			{
				// check for null Customer Id ...
				if ($customerId == null)  
				{
					$error = $orderId . ' has null customerId';
					ErrorHandler($error);
				} else {
					
					if ($createTime     == null) $createTime = 0;
					if ($status         == null) $status = ' ';
					if ($customerName   == null) $customerName = ' ';
					if ($customerEmail  == null) $customerEmail = ' ';
					if ($customerGroup  == null) $customerGroup = ' ';
					if ($customerGender == null) $customerGender = ' ';
					if ($discountAmount == null) $discountAmount = 0;
					if ($shippingAmount == null) $shippingAmount = 0;
					if ($subTotal       == null) $subTotal = 0;
					if ($grandTotal     == null) $grandTotal = 0;
					if ($totalPaid      == null) $totalPaid = 0;
					if ($itemCount      == null) $itemCount = 0;
					if ($totalQty       == null) $totalQty = 0;
					if ($weight         == null) $weight = 0;
					if ($paymentMethod  == null) $paymentMethod = ' ';
					if ($paymentTransactionId == null) $paymentTransactionId = 0;
					if ($payTime        == null) $paymentTime = ' ';				
					if ($recipient      == null) $recipient = ' ';				
					if ($shippingMethod == null) $shippingMethod = ' ';				
					if ($shippingAddress == null) $shippingAddress = ' ';				
					if ($postcode       == null) $postcode = ' ';				
					if ($telephone      == null) $telephone = ' ';				
					if ($mobile         == null) $mobile = ' ';				
					if ($remarks        == null) $remarks = ' ';			
					
					$zero = 0;	
					// Check order does not exist already ...
					$sql = "SELECT * FROM RECORHP WHERE OHORID = $orderId";
					$result = i5_query($sql);
					if (i5_num_rows($result) > 0)
					{
						$error = $orderId . ' already processed';
						ErrorHandler($error);
					} else {	

						// Write Header Record ...
					
						$sql = 'INSERT INTO RECORHP (OHORID, OHTIME, OHSTS, OHCUID, OHCNAM, OHCEMA, OHCGRP, OHCGEN, OHSTOT, OHDIS, OHSHP, OHAMT, 
													 OHPAID, OHITEM, OHQTY, OHWHT,  OHPAYM, OHPYID, OHPYTM, OHRECP, OHSHPM, OHSHPA, OHSHPP,
													 OHTEL,  OHMOB,  OHREM1, OHPIC)'
							. ' VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
						$req = i5_prepare($sql);
						
						$newRecord = array($orderId, $createTime, $status, $customerId, $customerName, $customerEmail, $customerGroup, $customerGender, 
										   $subTotal, $discountAmount, $shippingAmount, $grandTotal, $totalPaid, $itemCount, $totalQty, $weight, 
										   $paymentMethod, $paymentTransactionId, $payTime, $recipient, $shippingMethod, $shippingAddress, $postcode, 
										   $telephone, $mobile, $remarks, $zero) ;
						$result = i5_execute($req, $newRecord);

						// Now we need to retrieve all unique items.  We know how many items from $itemCount ....

						for ($count=0; $count<$itemCount; $count++)
						{
							$item = $row['orderItems'][$count];		
							$orderItemId = $item['orderItemId'];

							if ($orderItemId <> null)
							{
								$sku 			= $item['sku'];
								$productName 	= $item['productName'];
								$price		 	= $item['price'];
								$weight		 	= $item['weight'];
								$qty 		 	= $item['qty'];
								$subTotal    	= $item['subTotal'];
								$discountAmount = $item['discountAmount'];
								$rowWeight	 	= $item['rowWeight'];
								$rowTotal	 	= $item['rowTotal'];
								$options	 	= $item['options'];							

								
							    // make sure no nulls ...
								if ($sku     		== null) $sku = 0;
								if ($productName	== null) $productName = ' ';
								if ($price		    == null) $price = 0;
								if ($weight			== null) $weight = 0;
								if ($qty		    == null) $qty = 0;
								if ($subTotal	    == null) $subTotal = 0;
								if ($discountAmount == null) $discountAmount = 0;
								if ($rowWeight      == null) $rowWeight = 0;
								if ($rowTotal       == null) $rowTotal = 0;
								
								// Write Detail Record ...
								$sql = 'INSERT INTO RECORDP (ODORID, ODITID, ODSKU, ODPRC, ODDIS, ODWHT, ODQTY, ODAMT, ODRWHT, ODTOT)' 
								. ' VALUES (?,?,?,?,?,?,?,?,?,?)';
								$req = i5_prepare($sql);
								
								$newRecord = array($orderId, $orderItemId, $sku, $price, $discountAmount, $weight, $qty, $subTotal, $rowWeight, $rowTotal);
								$result = i5_execute($req, $newRecord);

								
								// Options array set up in $options ... expand and write.  Note ... we don;t know the assoc array names so we must get them
								foreach ($options as $element=>$value)
								{
									$sql = 'INSERT INTO RECOROP (OOORID, OOITID, OOOPT, OORES)' 
									. ' VALUES (?,?,?,?)';
									$req = i5_prepare($sql);
									
									$newRecord = array($orderId, $orderItemId, $element, $value);
									$result = i5_execute($req, $newRecord);
									
								}
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
			$description = array(array("Name" => "inOrderId", "IO" =>I5_IN, "Type" => I5_TYPE_CHAR, "Length" => "10"));
            $pgm = i5_program_prepare("EC104R", $description);
            if (!$pgm)
			{
				//if failed to call program write a log
				die(ErrorHandler("EC104R prepare failed from getorder.php, order#: $orderId"));			   
			}	
			
			// Set parameter with OrderId and call update program ...
			$parm = array("inOrderId"=>$orderId);			
            $ret = i5_program_call($pgm, $parm);
            if (!$ret)
			{
				//if failed to call program write a log
				die(ErrorHandler("EC104R program call failed from getorder.php, order#: $orderId"));			   
			}	
			
		}
		
	}
	
	if ($conn)
	{
		i5_close($conn);
	}	

	
function ErrorHandler($msg)
{
	// access variables defined outside function
	global $orderId;
	global $user;
	
	// Write error log to /home/ECOMMERCE folder
    $message   = $msg;
    $timestamp = date('d/m/Y H:i:s');
	$today 	   = date('Ymd');
	$time      = date('His');
    $log_file  = '/home/ECOMMERCE/log/err_' . $today . '.log';
	 
    error_log('['.$timestamp.'] INFO: '.$message.PHP_EOL, 3, $log_file);
	

	// Also write a record to RECERRP ...
	$sql = 'INSERT INTO RECERRP (ERPGM, ERUSR, ERTYP, ERDAT, ERTIM, ERORID, ERERR)' 
	. ' VALUES (?,?,?,?,?,?,?)';	
	$req = i5_prepare($sql);
	
	//$newRecord = array("getorder", "PHP", $user, $today, $time, $orderId, " ", " ", " ", "0", $msg);
	$newRecord = array("getorder", $user, "PHP", $today, $time, $orderId, $msg);

	$result = i5_execute($req, $newRecord);
	
}


?>

