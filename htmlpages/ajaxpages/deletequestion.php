<?php
// CALLED WHEN ADDING A NEW QUESTION TO QUIZ
// Parameters from _POST:
// quizid		: int : id of quiz question is in
// questionid	: int : id question to delete
include_once 'ajaxheader.php';
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
// Can only delete question if quiz state is inactive
if ($quizadmin->getQuizState($quizid) != '0') {
	die();
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