<?php
session_start();
include "functions.php";
ConnectToDataBase(DBName());

// If there is no current logged in user, redirect to the log in page
if(!isset($_SESSION['schedule'])){
	header('Location: index.html');
}

// Choose which semester's timetable to get
$schedule = $_SESSION['schedule'];
$sem = $_GET['sem'];

// extract course code from timetable.php form
$dept = $_POST["dept"];
$crs = $_POST["course"];
foreach($crs as $c){
	if ($dept == substr($c,0,DEPTCODELEN)){
		$crs = $c;
		break;
	}
}
if ($crs=="" || $dept== ""){
	header('Location: timetable.php');
}
?>
<html><title> Insert Course </title>
<style type="text/css">
.toppart{width: 100%;}
</style>
<title>UofT Course Scheduler</title>
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
<font face="Arial"><div align="center">
<table cellpadding='5'><tr><td align='center'>
<?php
echo "<b>Current schedule:</b><br>";
print displaySchedule($schedule,$sem);
?></td>
<td rowspan=2 align='center' width=340 style="vertical-align: top;">
<form action="timetable.php?sem=<?php echo $sem;?>" id='crs' method='post'>
<b>Insert Course: <?php echo $crs;?></b><br>
<font size=2><p align='left'>Red rows mean a conflict with something already 
chosen.You can still select a conflict section at your own risk.</p></font>
<?php 
// enforce a limit of 6 courses at a time
$mycourses = myCourses($schedule,$sem);
if (count($mycourses)==6){
	echo "You have reached the maximum of 6 courses. Please";
	echo "<a href='timetable.php?sem=$sem'> go back </a>";
	echo "and drop some courses.<br>";
	exit();
}
print displaySections($crs,$schedule,$sem); ?>
<br>
<input type="hidden" value="<?php echo $crs;?>" name="crs">
<input type="submit" value="Add" name="action">
<br>
<input type="submit" value="Cancel" name="action">
</form>
</td></tr>
<tr><td width='500' style="vertical-align: top;">
<p>
NOTE: You are responsible for verifying course prerequisites
or corequisites. Please consult the course descriptions or contact your
academic advisor if you are unsure.
</p>
</td></tr>
</table>
<br></font>
<footer>
<font face="Arial" size=2>
This course scheduling tool was created by Tzuo Shuin Yew (University of Toronto ECE Class of 2014). 
<br>
If you have any questions or contributions, please contact the UC. | <a href='disclaimer.html'>Website Disclaimer</a></font>
</footer>
</div>
</html>
