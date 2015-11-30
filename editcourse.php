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

if($_POST["mycourse"]=="Course:"){
	echo "You didn't select a course to manage, or you have nothing registered. Please";
	echo "<a href='timetable.php?sem=$sem'> go back </a>";
	echo "and select something.<br>";
	exit();
}

// extract course code from timetable.php form
$crs = $_POST["mycourse"];
if ($crs==""){
	header('Location: timetable.php');
}
$sections = courseSections($crs,$sem);
?>

<html><title> Edit Course </title>
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
<font face="Arial"><div align="center">
<table cellpadding='5'><tr><td align='center'>
<?php
echo "<b>Current schedule:</b><br>";
print displaySchedule($schedule,$sem);
?></td>
<td rowspan=2 align='center' width=340 style="vertical-align: top;">
<form action="timetable.php?sem=<?php echo $sem;?>" id='crs' method='post'>
<b>Edit Course: <?php echo $crs;?></b><br>
<font size=2><p align='left'>Red rows mean a conflict with something already 
chosen.You can still select a conflict section at your own risk.</p></font>
<?php print displaySections($crs,$schedule,$sem); ?>
<br>
<input type="hidden" value="<?php echo $crs;?>" name="crs">
Submit Changes: <input type="submit" value="Edit" name="action"><br>
Remove Course: <input type="submit" value="Remove" name="action"><br>
<input type="submit" value="Cancel" name="action">
</td></tr>
<tr><td width='500' style="vertical-align: top;">
<p>
NOTE: You are responsible for verifying course prerequisites
or corequisites. Please consult the course descriptions or contact your
academic advisor if you are unsure.
</p>
</td></tr>
</table>
</form>
<br><br>
<footer>
This course scheduling tool was created by Tzuo Shuin Yew (University of Toronto ECE Class of 2014). 
<br>
If you have any questions or contributions, please contact the UC. | <a href='disclaimer.html'>Website Disclaimer</a>
</footer>
</div>
</font>
</html>
