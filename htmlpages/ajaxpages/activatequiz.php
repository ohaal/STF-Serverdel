<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_GET ['quizid'] )) {
	$quizid = $_GET ['quizid'];
	if (! is_numeric ( $quizid )) {
		die ();
	}
} else {
	die ();
}

// Can only activate quiz if quiz state is inactive
if ($quizadmin->getQuizState($quizid) != '0') {
	die();
}
$quizadmin->activateQuiz( $quizid );
?>