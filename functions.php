<?php
include "credentials.php";

$DBHost = DBHost();

define("CRSCODELEN",9);
define("DEPTCODELEN",3);

// This function connects to a MySQL database
function ConnectToDataBase($DBName) {
	global $DBHost, $link;
	$User = DBUser();
	$Pass = DBPass();
	// connect to the server	 
	if (!($link=mysql_pconnect("$DBHost","$User","$Pass"))) {
		$ErrMsg=" Database connection error: " . mysql_errno() .
				" " . mysql_error();
		DisplayErrMsg($ErrMsg);
		return 0;
	}

	// select the database
	if(!(mysql_select_db($DBName,$link))) {
		$ErrMsg="Error in selecting $DBName database: ".
				mysql_errno() . " " . mysql_error();
		DisplayErrMsg($ErrMsg);
		exit();
	}
}

// This function displays an error message if a query to the MySQL database is unsuccessful
function DisplayErrMsg($message) {
	// Error codes are auto generated depending on the error and should be looked up in google.
	print "<BLOCKQUOTE>
		   <BLOCKQUOTE>
			<BLOCKQUOTE>
			<H3><FONT COLOR=\"#cc0000\">$message</font></H3>
			</BLOCKQUOTE></BLOCKQUOTE></blockquote>\n";
}

/* This function simplifies the querying process in MySQL by incorporating 
   the automatic error function*/
function Query($query){
	global $link;
	if(!($result=mysql_query($query,$link))) {
		$ErrMsg = "Bad query to database $DBName: Error Number ".mysql_errno().
				  "<br>".mysql_error();
		DisplayErrMsg($ErrMsg);
		echo "The bad query is $query\n";
		exit;
	}
	return $result;
}

// This function generates the navigation bar at the top of the page.
function navigationBar($sem) {
	// use an array to store titles and links
	$arNavBar["Fall"] = "timetable.php?sem=fall";
	$arNavBar["Winter"] = "timetable.php?sem=winter";
	
	foreach($arNavBar as $key => $value) {
		// title is bolded and unlinked if user is already on corresponding page
		if (strtolower($key)==$sem)
			echo "<b>$key</b>"; 
		else
			echo "<a href=\"$value\">$key</a>"; // otherwise title is linked
		if ($value != end($arNavBar))
			echo " | "; // separate individual titles with "|"
	}
}

// This function extracts the departments (table names in database)
function deptList($sem){
	$result = Query("SHOW TABLES LIKE '%_$sem'");
	$tables = array();
	while ($row = mysql_fetch_row($result)) {
		$tables[] = substr($row[0],0,DEPTCODELEN);
	}
	return $tables;
}

// This function extracts the listing of lec/tut/pra sections for a specific course
function courseSections($course,$sem){
	// extract the department so we know which table to query
	$dept = substr($course,0,DEPTCODELEN)."_$sem";
	$query = "SELECT DISTINCT SECTION FROM $dept WHERE NAME = '$course'";
	$sections = array();
	$result = Query($query);
	while ($row = mysql_fetch_assoc($result)){
		// store all the sections in an array
		$sections[]= $row["SECTION"];
	}
	return $sections;
}

/* This function returns all the existing course codes for use in a select form
 It will leave out courses that the student has already registered in*/
function courseList($sched,$dept){
	// first find all the courses that the student is already taking
	$taken = myCourses($sched, substr($dept,DEPTCODELEN+1));
	$query = "SELECT DISTINCT NAME FROM $dept ORDER BY NAME ASC";
	// declare a blank array to store the entire list of courses
	$courseList = array();
	$result = Query($query);
	while ($row = mysql_fetch_assoc($result)){
		// remove a course from the big array if the student is already taking it
		if (!in_array($row["NAME"],$taken))
			$courseList[]= $row["NAME"];
	}
	return $courseList;
}

// This function returns all the existing course codes that the student selected
function myCourses($sched,$sem){
	// declare a blank array to store the list of chosen courses
	$taken = array();
	$query = "SELECT DISTINCT NAME FROM $sched WHERE NAME LIKE '%Y' OR ";
	// If fall semester, get onto F and Y courses
	// if winter semester, get only S and Y courses
	if ($sem=="fall")
		$query .= "NAME LIKE '%F' ";
	else if ($sem=="winter")
		$query .= "NAME LIKE '%S' ";
	$query .= "ORDER BY NAME ASC";
	$result = Query($query);
	while ($row = mysql_fetch_row($result)){
		$taken[]= $row[0];
	}
	return $taken;
}

/* This function adds a course to the student's timetable
 inputs: course code, lecture section, tutorial section, practical section
 e.g. ECE334H1F, LEC 01, TUT 02, PRA 03*/
 function insertCourse($sched,$sem,$crs,$lec,$tut,$pra){
 	// extract the department so we know which table to query
	$dept = substr($crs,0,DEPTCODELEN)."_$sem";
	// Query the course listing table for all rows matching these inputs
	$query = "INSERT INTO $sched (SELECT DISTINCT * FROM $dept 
			WHERE NAME = '$crs' AND 
			(SECTION = '$lec' OR SECTION = '$tut' OR SECTION = '$pra'))";
	$result = Query($query);
}

// This function modifies lec/tut/pra section
function editCourse($sched,$sem,$crs,$lec,$tut,$pra){
	// easiest way - just delete the whole course to purge the old sections
	removeCourse($sched,$crs);
	// then reinsert the course with the new sections
	insertCourse($sched,$sem,$crs,$lec,$tut,$pra);
}

// This function drops the course
function removeCourse($sched,$crs){
	Query("DELETE FROM $sched WHERE NAME='$crs'");
}

// This function checks for conflicts
function checkConflicts($sched,$sem,$crs,$section,$day,$start,$end){
	// preemptive measure if the student selected a course that has no
	// meeting sections, such as ESC499. No error flag will get thrown.
	// editcourse.php and insertcourse.php call this function very early
	if ($day==""){
		echo "This course has no meeting sections!<br>";
		echo "<a href='timetable.php?sem=$sem'> Go Back </a><br><br>";
		exit();
	}
	// check the student's other chosen courses for cases where a course of
	// a different name in the same semester overlaps with the timings for
	// the to-be-added course.
	$query = "SELECT * FROM $sched WHERE NAME != '$crs' ";
	if ($sem=="fall")
		$query .= "AND (NAME LIKE '%Y' OR NAME LIKE '%F') ";
	else if ($sem=="winter")
		$query .= "AND (NAME LIKE '%Y' OR NAME LIKE '%S') ";
	$query .= "AND DAY='$day' AND NOT (FINISH <= '$start' OR START >= '$end')";
	$result = Query($query);
	return mysql_num_rows($result);
}

// This function is called if the "Filter Conflicts" option on timetable.php is
// turned on. It will only allow courses to be displayed in the select forms
// if they have at least one of each applicable section that does not conflict
// with what's already present in the table
function filterConflict($sched,$sem,$course){
	// exit with green light if schedule is empty
	if (!count(myCourses($sched,$sem)))
		return 1;
	$arr = array('L','T','P');
	$dept = substr($course,0,DEPTCODELEN)."_$sem";
	foreach($arr as $s){
		$result = Query("SELECT DISTINCT SECTION FROM $dept 
						WHERE NAME = '$course'
						AND SECTION LIKE '$s%' AND DAY NOT LIKE ''");
		$count = mysql_num_rows($result);
		if ($count){
			$numconflicts = 0;
			$section = "";
			while(list($section)=mysql_fetch_row($result)){
				$result2 = Query("SELECT DAY,START,FINISH FROM $dept WHERE
								NAME='$course' AND SECTION='$section'");
				while(list($day,$start,$end) = mysql_fetch_row($result2)){
					$numconflicts = checkConflicts($sched,$sem,$course,
										$section,$day,$start,$end);
				}
				if ($numconflicts==0) {
					break;
				}
			}
			if ($numconflicts>0){
				return 0;
			}
		} 
	}
	return 1;
}

// This function adds multiple courses at the same time. The student does not
// get to choose what sections he wants, though he can modify them after the 
// timetable is auto generated
function quickAdd($sched,$sem,$courses){
	// eliminate duplicates
	$courses = array_unique($courses);
	// first add meeting sections that only have one option
	foreach($courses as $course){
		$dept = substr($course,0,DEPTCODELEN)."_$sem";
		$arr = array('L','T','P');
		foreach($arr as $s){
			$result = Query("SELECT DISTINCT SECTION FROM $dept 
							WHERE NAME = '$course'
							AND SECTION LIKE '$s%' AND DAY NOT LIKE ''");
			$count = mysql_num_rows($result);
			if ($count==1){
				Query("INSERT INTO $sched (SELECT DISTINCT * FROM $dept 
					WHERE NAME = '$course' AND SECTION LIKE '$s%')");
			} 
		}
	}
	// then try to add other sections without conflicting
	foreach($courses as $course){
		$dept = substr($course,0,DEPTCODELEN)."_$sem";
		$arr = array('L','T','P');
		foreach($arr as $s){
			$result = Query("SELECT DISTINCT SECTION FROM $dept 
							WHERE NAME = '$course'
							AND SECTION LIKE '$s%' AND DAY NOT LIKE ''");
			$count = mysql_num_rows($result);
			if ($count>1){
				$numconflicts = 0;
				$section = "";
				while(list($section)=mysql_fetch_row($result)){
					$result2 = Query("SELECT DAY,START,FINISH FROM $dept WHERE
									NAME='$course' AND SECTION='$section'");
					while(list($day,$start,$end) = mysql_fetch_row($result2)){
						$numconflicts = checkConflicts($sched,$sem,$course,
											$section,$day,$start,$end);
					}
					if ($numconflicts==0) {
						Query("INSERT INTO $sched (SELECT DISTINCT * FROM $dept
								WHERE NAME = '$course' AND SECTION='$section')");
						break;
					}
				}
				if ($numconflicts>0){
					Query("INSERT INTO $sched (SELECT DISTINCT * FROM $dept
							WHERE NAME = '$course' AND SECTION='$section')");
				}
			}
		}
	}
}

// This function converts the student's selected courses 
// to a calendar-like 2D array
function arraySchedule($sched,$sem){
	$data = array();
	/*The table should span a dynamic range of hours. If the earliest class
	is at 11 am and the latest class ends at 4 pm, the table should span 11-4
	that way there are no blank rows at the top or the bottom.
	to do that, we will have to query the schedule for the earliest start time
	and the earliest end time*/
	$query = "SELECT MIN(START),MAX(FINISH) FROM $sched WHERE NAME LIKE '%Y' OR ";
	// If we're displaying a fall timetable, only F and Y courses 
	// should be queried. For winter, only S and Y courses should be queried.
	if ($sem=="fall")
		$query .= "NAME LIKE '%F'";
	else if ($sem=="winter")
		$query .= "NAME LIKE '%S'";
	$result = Query($query);
	list($earliest,$latest) = mysql_fetch_row($result);
	//Pull out the hour portion of the earliest start and end times
	$earliest = date("H",strtotime($earliest));
	$latest = date("H",strtotime($latest));
	// check for 30 min increments
	$check = mysql_num_rows(Query("SELECT * FROM $sched WHERE 
									START LIKE '%:30:00' OR 
									FINISH LIKE '%:30:00'"));
	//Now build the 2D array.
	for ($i=$earliest;$i<$latest;$i++){
		$data[]=array(0=>"$i:00:00","","","","","",);
		// insert 30 min increments if needed
		if ($check)
			$data[]=array(0=>"$i:30:00","","","","","");
	}
	// Here we're assigning day names as keys and numbers as values
	// the numbers will identify the index in the 2D array for insertion
	// the day name will correspond to the "DAY" field in each tuple
	$days = array("Mon"=>1,"Tue"=>2,"Wed"=>3,"Thu"=>4,"Fri"=>5);
	// If we're displaying a fall timetable, only F and Y courses 
	// should be queried. For winter, only S and Y courses should be queried.
	$query = "SELECT DISTINCT * FROM $sched WHERE NAME LIKE '%Y' OR ";
	if ($sem=="fall")
		$query .= "NAME LIKE '%F'";
	else if ($sem=="winter")
		$query .= "NAME LIKE '%S'";
	$result = Query($query);
	while(list($name,$section,$day,$start,$end,$loc) = mysql_fetch_row($result)){
		// start populating the array in calendar form
		for($i=0;$i<count($data);$i++){
			// grab the time of each row (we inserted just now)
			$time = strtotime($data[$i][0]);
			// Identify the index of the $data[$i] row to insert the course into
			$j = $days[$day];
			if ($time>=strtotime($start) && $time <strtotime($end)){
				$s = convertTime($start);
				$e = convertTime($end);
				// if the array slot is filled, need to flag conflict
				if ($data[$i][$j]!=""){
					if (strpos($data[$i][$j],"CONFLICT")===false)
						$data[$i][$j] = "<b>CONFLICT</b><br>".$data[$i][$j];
					$data[$i][$j] .= "<br>";
				}
				$data[$i][$j] .= "$name<br><font size=2>$section
								<br>$s-$e<br>$loc</font>";
			}
		}
	}
	return $data;
}

// This function displays the timetable with all the student's chosen courses
function displaySchedule($sched,$sem){
	// get the chosen courses into a 2D array form
	$data = arraySchedule($sched,$sem);
	
	// dynamically generate the table in HTML
	$schedule = "<table class='records' border='1' align='center' 
				cellspacing='0' cellpadding='3'>
				<tr><th>Time<th>Mon<th>Tue<th>Wed<th>Thu<th>Fri</tr>";

	// we want to format the table such that one course section taking multiple
	// vertically consecutive cells will be shown as one huge cell spanning
	// multiple rows. E.g. a course going from 6-9 on Monday would mean 
	// the cells for Monday 6-9 would be merged into one big cell.
	
	// this variable is a 2D array. It tracks duplicates in each weekday column
	// of the schedule (our $data array)
	$last = array();
	for($i=0;$i<count($data);$i++){
		list($Time,$Mon,$Tue,$Wed,$Thu,$Fri) = $data[$i];
		// the 0th index of our 2D array is used for the schedule time indicator
		// so we have to start from 1 when assigning keys
		$arr = array(1=>$Mon,$Tue,$Wed,$Thu,$Fri);
		$schedule .= "<tr>";
		// convert the time to 12-hr form, and chop off trailing :00 (seconds)
		if ($check && $i%2==0){
			$Time = convertTime($Time);
			// Don't want to show x:30 times. Make x:00 times span 2 rows
			$schedule .= "<td rowspan='2'>$Time</td>";
		}
		else if (!$check){
			$Time = convertTime($Time);
			$schedule .= "<td>$Time</td>";
		}
		// this 2D array will track the size of each group of duplicates 
		// in each weekday column
		$rowspan = array();
		// initialize the duplicate group sizes to zero
		foreach($arr as $key=>$value){
			$rowspan[$key] = 0;
		}
		// predefine 6 colours
		$cols = array("F7E967","A9CF54","70B7BA","45D99E","A062DE","DB9A37");
		$mycourses = myCourses($sched,$sem);
		if (count($mycourses)){
			$cols2 = array_slice($cols,0,count($mycourses));
			$colslist = array_combine($mycourses,$cols2);
		}
		// iterate through the schedule table and hunt for duplicates and adjust
		// rowspan values where necessary
		foreach($arr as $key=>$value){
			if ($value!=""){
				// use only the 9-char course code, i.e. ABC123H1F as an index
				// for the colour array
				$name = substr($value,0,CRSCODELEN);
				for($j=$i;$data[$j][$key]!==$last[$key] && $j < count($data) &&
					$data[$j][$key]==$data[$i][$key];$j++){
					$rowspan[$key]++;
				}
				if ($rowspan[$key]>0){
					$last[$key]=$data[$i][$key];
					// if there's no conflict, pick one of the 6 colours
					if (strpos($value,"CONFLICT")===false){
						$schedule .= "<td bgcolor={$colslist[$name]} 
									rowspan='{$rowspan[$key]}'>".$value.
									"</td>";
					} else {// colour the cell red if there's a conflict
						$schedule .= "<td bgcolor='F1433F' 
									rowspan='{$rowspan[$key]}'>".$value."</td>";
					}
				}
			}
			else // if the cell is blank, then it's blank!
				$schedule .= "<td></td>";
		}
		$schedule .="</tr>";
	}
	$schedule .= "</table>";
	
	return $schedule;
}

// This function displays all the possible sections for a course
// and highlights which ones conflict with what's already added.
function displaySections($crs,$sched,$sem){
	//convert the sections to a calendar-like 2D array
	$data = array();
	$dept = substr($crs,0,DEPTCODELEN)."_$sem";
	$result = Query("SHOW TABLES LIKE '$dept'");
	if (!mysql_num_rows($result)){
		return "Sorry, the course could not be found.<br>";
	}
	$result = Query("SELECT DISTINCT * FROM $dept WHERE NAME='$crs'");
	if (!mysql_num_rows($result)){
		return "Sorry, the course could not be found.<br>";
	}
	while(list($name,$section,$day,$start,$end,$loc) = mysql_fetch_row($result)){
		// get the currently occupied timings to make it easier for 
		// the student to see what could conflict
		$numconflicts = checkConflicts($sched,$sem,$name,$section,$day,$start,$end);
		$start = convertTime($start);
		$end = convertTime($end);
		if($numconflicts)
			$data[] = array($section,$day,$start,$end,$loc,1);
		else
			$data[] = array($section,$day,$start,$end,$loc,0);
	}
	// dynamically generate the table in HTML
	$table = "<table class='records' border='1' align='center' 
				cellspacing='0' cellpadding='2'>
				<tr><th>Section<th>Day<th>Start<th>End<th>Location</tr>";
	$table .= "<tr><th colspan=5>Lectures</th></tr>";
	// If a section occurs more than once a week, display its name as 
	// a large cell spanning multiple rows for easier differentiation
	$last = FALSE;
	$leccount=0;
	$tutcount=0;
	$pracount=0;
	for($i=0;$i<count($data);$i++){
		$rowspan = 0;
		list($section,$day,$start,$end,$loc,$conf) = $data[$i];
		if ($section[0]=='T' && $tutcount==0)
			$table .= "<tr><th colspan=5>Tutorials</th></tr>";
		if ($section[0]=='P' && $pracount==0)
			$table .= "<tr><th colspan=5>Practicals</th></tr>";
		$table .= "<tr>";
		// track multiple occurences of section names and adjust rowspan
		for($j=$i;$data[$j][0]!==$last && $j < count($data) &&
			$data[$j][0]==$data[$i][0];$j++){
			$rowspan++;
		}
		if ($rowspan>0){
			$last = $section;
			$table .= "<td rowspan='$rowspan'>";
			$table .= "<input type='radio' ";
			switch($section[0]){
				case 'L': $table .= "name='lec' ";
						$leccount++; 
						$table .= ($leccount==1)?"checked ":"";
						break;
				case 'T': $table .= "name='tut' ";
						$tutcount++;
						$table .= ($tutcount==1)?"checked ":"";
						break;
				case 'P': $table .= "name='pra' "; 
						$pracount++; 
						$table .= ($pracount==1)?"checked ":"";
						break;
				default: break;
			}
			$table .= "value='$section'>$section</td>";
		}
		if ($conf==1) {
			$table .= "<td bgcolor='F1433F'>$day</td>
						<td bgcolor='F1433F'>$start</td>
						<td bgcolor='F1433F'>$end</td>
						<td bgcolor='F1433F'>$loc</td>";
		} else
			$table .= "<td>$day</td><td>$start</td><td>$end</td><td>$loc</td>";
		$table .= "</tr>";
	}
	$table .="</table>";
	return $table;
}

// this function converts 24-hour H:i:s time to 12-hour g:i time
function convertTime($time){
	date_default_timezone_set('Asia/Hong_Kong');
	return date("g:i",strtotime($time));
}

// this function creates a user-specific table in the database
function makeSchedule($sched){
	// make sure the user registered a username of at least 4 characters
	if(strlen($sched)<4)
		return -1;
	Query("CREATE TABLE IF NOT EXISTS $sched 
			(NAME text, SECTION text, DAY text, 
			START time, FINISH time, LOCATION text)");
	return 0;
}

// this function empties the timetable in one move
function purge($sched,$sem){
	$query = "DELETE FROM $sched WHERE NAME LIKE '%Y' OR ";
	if ($sem=="fall")
		$query .= "NAME LIKE '%F'";
	else if ($sem=="winter")
		$query .= "NAME LIKE '%S'";
	Query($query);
}

