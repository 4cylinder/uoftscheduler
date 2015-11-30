<?php
include "functions.php";
ConnectToDatabase(DBName());

function update($sem){
	foreach(glob("./$sem/*.csv") as $filename){
		$dept = substr($filename,0,-4);
		if ($sem=="fall")
			$dept = substr($dept,7);
		else if ($sem=="winter")
			$dept = substr($dept,9);
		echo $dept."\n";
		if (($handle = fopen($filename, "r")) !== FALSE) {
			Query("DROP TABLE IF EXISTS $dept");
			Query("CREATE TABLE IF NOT EXISTS $dept (NAME text, SECTION text, 
				DAY text, START time, FINISH time, LOCATION text)");
		
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$q = "INSERT INTO $dept VALUES (";
				foreach($data as $key=>$value) {
				    $q .= "'$value',";
				}
				$q = substr($q,0,-1);
				$q .= ")";
			  	Query($q);
			}
			fclose($handle);
		}
	}
}

update("fall");
update("winter");

?>
