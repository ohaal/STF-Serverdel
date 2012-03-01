<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_GET ['teamid'] )) {
	$teamid = $_GET ['teamid'];
	
	if (!is_numeric ( $teamid )) {
		die();
	}
}
if (isset ( $_GET ['teamname'] )) {
	$teamname = $_GET ['teamname'];
	if (strlen ( $teamname ) <= 0) {
		die();
	}
}

//print_r($teamname);
$quizadmin->setTeamName ($teamid, $teamname);

// return nothing?
?>