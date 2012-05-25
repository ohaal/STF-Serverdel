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

// Can only activate quiz if quiz state is inactive
if ($quizadmin->getQuizState( $quizid ) != '0') {
	die();
}
$quizKeyword = $quizadmin->getQuizKeyword( $quizid );
// Can only activate quiz if other quiz with same keyword is not active
if ($quizadmin->getQuizKeywordExistsAndActive( $quizKeyword )) {
	die();
}
$quizadmin->activateQuiz( $quizid );
?>