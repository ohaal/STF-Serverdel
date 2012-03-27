<?php
// CALLED WHEN ADDING A NEW QUIZ
// Parameters from _GET:
// quizname 	: string : name of quiz we are going to add
// quizkeyword  : string : keyword for receiving SMS
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_GET ['quizname'] ) && isset( $_GET ['quizkeyword'] ) ) {
	$quizName = $_GET ['quizname'];
	$quizKeyword = $_GET ['quizkeyword'];
	
	if (strlen ( $quizName ) > 0 && strlen( $quizKeyword ) > 0) {
		$quizadmin->addQuizName ( $quizName, $quizKeyword );
	}
}
// return nothing?
?>