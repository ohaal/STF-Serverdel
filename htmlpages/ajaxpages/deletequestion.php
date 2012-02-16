<?php 
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_POST ['quizid'] )) {
	$quizid = $_POST ['quizid'];
	if (! is_numeric ( $quizid )) {
		die ();
	}
} else {
	die ();
}
if (isset ( $_POST ['questionid'] )) {
	$questionid = $_POST ['questionid'];
	if (! is_numeric ( $questionid )) {
		die ();
	}
} else {
	die ();
}
$quizadmin->deleteQuestion ( $quizid, $questionid );
?>