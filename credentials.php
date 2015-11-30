<?php

//Most modern apache servers disable traditional global variables across pages
//for security reasons. These simple functions serve as a substitute.

function DBHost(){
	return "localhost";
}

function DBName(){
	return "timetable";
}

function DBUser(){
	return "root";
}

function DBPass(){
	return "password";
}

?>
