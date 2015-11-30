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

// Get list of departments (APS, CSC, etc...)
$depts = deptList($sem);
// Get the already-added courses
$mycourses = myCourses($schedule,$sem);
$limit = 6-count($mycourses);
?>
<script language='javascript'>
// When the student clicks on one of the depts (e.g. ACT, CSC, etc) it will
// populate the middle crslist with all the corresponding courses
function deptselect(element){
	var selectedValue = element.value;
	clearlistbox('crslist');
	listbox_selectall(selectedValue,true);
	listbox_copyacross(selectedValue, 'crslist');
}
// Transfer contents of a listbox without keeping them in the source
function listbox_moveacross(sourceID, destID) {
	var src = document.getElementById(sourceID);
	var dest = document.getElementById(destID);
	var limit = dest.size;
	for(var count=0; count < src.options.length; count++) {
		if(src.options[count].selected == true) {
			// Only 6 courses allowed at the max!
			if (destID=='toadd' && dest.options.length==limit)
				return 0;
			var option = src.options[count];
			var newOption = document.createElement("option");
			newOption.value = option.value;
			newOption.text = option.text;
			try {
				dest.add(newOption, null); //Standard
				src.remove(count, null);
			}catch(error) {
				dest.add(newOption); // IE only
				src.remove(count);
			}
			count--;
		}
	}
}
// Copy options from one listbox to another without affecting the source
// Used for copying from the hidden course lists to the middle crslist box
function listbox_copyacross(sourceID, destID) {
	var src = document.getElementById(sourceID);
	var dest = document.getElementById(destID);
	for(var count=0; count < src.options.length; count++) {
		if(src.options[count].selected == true) {
			var option = src.options[count];
			var newOption = document.createElement("option");
			newOption.value = option.value;
			newOption.text = option.text;
			try {
				dest.add(newOption, null); //Standard
			}catch(error) {
				dest.add(newOption); // IE only
			}
		}
	}
}
// Empty the select listbox in one move
function clearlistbox(id){
	var lb = document.getElementById(id);
	var i;
	for(i=lb.options.length-1;i>=0;i--){
		lb.remove(i);
	}
}
// Select every option in the listbox at once
function listbox_selectall(listID, isSelect) {
	var listbox = document.getElementById(listID);
	for(var count=0; count < listbox.options.length; count++) {
		listbox.options[count].selected = isSelect;
	}
}

</script>
<html><title> Add Multiple Courses </title>
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
<h3>Add Multiple Courses - <?php echo $sem; ?></h3>
<table cellpadding='5'><tr><td align='center'>
<tr>
<td><select name='dept' size='20' id = 'depts' 
	form='courses' onClick="deptselect(this);">
<?php
// generate a select form field to choose a department
foreach($depts as $value){
	echo "<option>$value</option>";
}
?></select>
</td>
<td>
<?php
// Generate the (hidden) select form fields for the courses of each department
foreach($depts as $value){
	echo "<select multiple name='course[]' size = '20' id = '$value'>";
	$courses = courseList($schedule,substr($value,0,3)."_$sem");
	foreach($courses as $c){
		echo "<option value='$c'>$c</option>";
	}
	echo "</select>";
	// use javascript to HIDE these fields 
	// Unlike on timetable.php, selecting a dept won't unhide these fields,
	// but will copy their contents into the crslist select box
	echo "<script language='javascript'>";
	echo "document.getElementById('$value').style.display = 'none'";
	echo "</script>";
}
?>
<select multiple name='crslist' style="width:105px;" size = '20' id = 'crslist'>
</select>
</td>
<td>
<a href="#" onclick="listbox_moveacross('crslist', 'toadd')">&gt;&gt;</a>
<br>
<a href="#" onclick="listbox_moveacross('toadd', 'crslist')">&lt;&lt;</a>
</td>
<td>
Choose up to <?php echo $limit;?> courses<br>
<form action="timetable.php?sem=<?php echo $sem;?>" method="post" onsubmit="return listbox_selectall('toadd', true);">
<select multiple name="toadd[]" style="width:105px;" size="<?php echo $limit;?>" id="toadd">
</select>
<input type="hidden" value="QuickAdd" name="action">
<input type="submit" value="Go!" name="submit">
<br>
<input type="submit" value="Cancel" name="action">
</form>
</td>
</tr>
<tr><td colspan=4 width=500>Add up to six courses and click Go! The program will
do its best to provide a timetable with as few conflicts as possible. Please note that
this is currently in beta and the program may not always find the best combination. </td></tr>
</table>
<br>
<footer>
<font face="Arial" size=2>
This course scheduling tool was created by Tzuo Shuin Yew (University of Toronto ECE Class of 2014). 
<br>
If you have any questions or contributions, please contact the UC. | <a href='disclaimer.html'>Website Disclaimer</a></font>
</footer>
</div>
</font>
</html>
