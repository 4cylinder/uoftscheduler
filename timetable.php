<?php
session_start();
include "functions.php";
ConnectToDataBase(DBName());

if ($_POST['utor']){
	$_SESSION['schedule'] = $_POST['utor'];
	$result = makeSchedule($_POST['utor']);
	if($result <0)
		header('Location: index.html');
}

// If there is no current logged in user, redirect to the log in page
if(!isset($_SESSION['schedule'])){
	header('Location: index.html');
}

// Choose which semester's timetable to get
$schedule = $_SESSION['schedule'];

if ($_GET['sem']=="")
	$sem = "fall";
else
	$sem = $_GET['sem'];

// get all POST variables
extract($_POST);
switch ($action){
	case "Add": insertCourse($schedule,$sem,$crs,$lec,$tut,$pra); break;
	case "Edit": editCourse($schedule,$sem,$crs,$lec,$tut,$pra); break;
	case "Remove": removeCourse($schedule,$crs); break;
	case "AddNew": addNewCourse($schedule,$dept,$code,$type,$section,$day,$start,$end); break;
	case "Purge": purge($schedule,$sem); break;
	case "QuickAdd": quickAdd($schedule,$sem,$toadd); break;
	default: break;
}

// Get list of departments (APS, CSC, etc...)
$depts = deptList($sem);

// Decide whether to turn on the conflict filter or not
if ($_GET["filter"])
	$_SESSION["filter"] = $_GET["filter"];
$filter = $_SESSION["filter"];
?>
<script language='javascript'>
// This javascript function is triggered by an onclick in the form for
// selecting department. It will show or hide the corresponding select form
// for the course codes.
function deptselect(){
	//var selectedval = element.value;
	var list = document.getElementById('depts');
	for(var i=0; i < list.options.length; i++){
		var dept=list.options[i].value;
		if (list.options[i].selected==true)
			document.getElementById(dept).style.display = 'inline';
		else
			document.getElementById(dept).style.display = 'none';
	}
}
</script>

<html><title> UofT Course Scheduler </title>
<style type="text/css">
.toppart{width: 100%;}
</style>
<table cellspacing='2' class="toppart" align='center'>
<tr><td colspan=8 align="left" bgcolor=1D0E80 height=80 style="vertical-align: bottom;">
<font face="Arial" color="white" size=5><br>
University of Toronto: Course Scheduler
</font></td>
</tr><tr height=12>
<td bgcolor=FF8080></td><td bgcolor=FFD851></td><td bgcolor=6ACF54></td>
<td bgcolor=45D99E></td><td bgcolor=408080></td><td bgcolor=00A2E8></td>
<td bgcolor=A349A4></td><td bgcolor=DB9A37></td>
</tr></table>
<br>
<div align="center">
<font face="Arial">
<table cellpadding='5'><tr><td align='center'>
<?php navigationBar($sem); print displaySchedule($schedule,$sem); ?></td>
<td align='left' style="vertical-align: top;">
<br>
Add a new course:<br>
<form action="insertcourse.php?sem=<?php echo $sem;?>" id='courses' method='post'>
<select name='dept' id = 'depts' form='courses' onChange="deptselect();">
<?php
// generate a select form field to choose a department
foreach($depts as $value){
	echo "<option value='$value'>$value</option>";
}
?></select>
<?php
// Generate the select form fields for the courses of each department
foreach($depts as $value){
	echo "<select name='course[]' id = '$value' form='courses' style='display:none'>";
	$courses = courseList($schedule,substr($value,0,DEPTCODELEN)."_$sem");
	foreach($courses as $course){
		if ($filter!="on" || filterConflict($schedule,$sem,$course)>0)
			echo "<option value='$course'>".substr($course,DEPTCODELEN)."</option>";
	}
	echo "</select>";
}
?>
<input type="submit" value="Go!" name="submit">
</form>
Manage selected courses:<br>
<form action="editcourse.php?sem=<?php echo $sem;?>" method='post'>
<select name="mycourse" id='mycourses'>
<?php
// print out a select form to show the courses the student already registered in
$mycourses = myCourses($schedule,$sem);
foreach($mycourses as $key=>$value){
	echo "<option value='$value'>$value</option>";
}
?>
</select>
<input type="submit" value="Go!" name="submit">
</form>
<form action="timetable.php?sem=<?php echo $sem;?>" id='purge' method='post'>
<input type="hidden" value="Purge" name="action">
<input type="submit" value="Remove all courses" name="submit">
</form>
<p>Filter Conflicts:
<input type='radio' name='filter' value='on' <?php if ($filter=='on') echo " checked "; ?>
onclick="document.location.href='timetable.php?sem=<?php echo $sem;?>&filter=on'"> On
<input type='radio' name='filter' value='off' <?php if ($filter!='on') echo " checked "; ?>
onclick="document.location.href='timetable.php?sem=<?php echo $sem;?>&filter=off'">Off
</p>
<p align='center'><a href="index.html"><b>Log Out</b></a></p>
<p><br></p>
<table align='left' cellpadding='8'><tr><td width=215 bgcolor=C6CBBE >
<b>Beta Feature:</b><br><br>
Use the <a href="quickadd.php?sem=<?php echo $sem;?>">autoscheduler</a> 
to select all of your courses at once and have their sections scheduled with
minimal time conflicts.
</td></tr></table>
</td></tr>
<tr><td width='500'>
<p>
NOTE: You are responsible for verifying course prerequisites
or corequisites. Please consult the course descriptions or contact your
academic advisor if you are unsure.
</p>
</td></tr>
</table>
<br>
</font>
<footer>
<font face="Arial" size=2>
This course scheduling tool was created by Tzuo Shuin Yew (University of Toronto ECE Class of 2014). 
<br>
If you have any questions or contributions, please contact the UC. | <a href='disclaimer.html'>Website Disclaimer</a></font>
</footer>
</div>
</html>
