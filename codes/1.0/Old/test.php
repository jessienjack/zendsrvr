<?php

//$test = '{"data":"{\"data"\:\"received\"}","errors":[]}';
//$test = '{"data":"{\"data\":\"received\"}","errors":[{"code":5,"message":"Session expired. Try to relogin.","data":null}]}';
//$test = '{"data":"{"\data\":"\received\"}","errors":[{"code":5,"message":"Session expired. Try to relogin.","data":null}]}';

$test = '{"data":"{\"data\":\"received\"}","errors":[]}';

$jsonObject = json_decode($test);
//echo "test is </br>";
var_dump($jsonObject);
//echo "</br>";

foreach ($jsonObject as $name=>$value) {


	if ($name == 'data')
	{
		echo "name: $name</br>value: $value </br>";
		foreach ($value as $entry=>$value1)
		{	
			var_dump($value1);
			echo $value1->data;
			//echo $value1['data'];
		}
	}	
	if ($name == 'errors')
	{
		//echo "name: $name</br>value: $value </br>";
		foreach ($value as $entry=>$value1)
		{	
			var_dump($value1);
			echo "</br>";
			echo $value1->message;
		}
	}	
}


#{"data":null,
#	"errors":[{"code":5,"message":"Session expired. Try to relogin.","data":null}]
#}

#{"data":"[{\"data\":\"received\"}]",
#	"errors":[]}';

#{"data":
#	"{\"data\":\"received\"}",
#		"errors":[{"code":5,"message":"Session expired. Try to relogin.","data":null}]}


#'{"data":"{"\data\":"\received\"}",
#  "errors":[{"code":5,"message":"Session expired. Try to relogin.","data":null}]}';

















?>