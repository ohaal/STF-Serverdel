<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
if (isset( $_POST['quizid'] )) {
	$quizid = $_POST['quizid'];
	if (!is_numeric( $quizid )) {
		die();
	}
}
else {
	die();
}
$quizadmin = new quizAdmin();

// Can only deactivate quiz if quiz is active (TODO: and has no answers)
if ($quizadmin->getQuizState( $quizid ) != '1') {
	die();
}
$quizadmin->deactivateQuiz( $quizid );
?>