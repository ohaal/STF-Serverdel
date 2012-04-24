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
	$quizKeyword = strtolower($_GET ['quizkeyword']);
	
	// Keyword must be alphanumeric (a-z, 0-9 or æ, Æ, ø, Ø, å, Å, case insensitive) and between 1 and 20 characters
	$isNordicAlnum = preg_match('/^[a-z0-9\x{00C6}\x{00E6}\x{00C5}\x{00E5}\x{00D8}\x{00F8}]{1,20}$/iu', $quizKeyword);
	if (!$isNordicAlnum) {
		die();
	}

	// Quiz name length must be between 1 and 200 (DB restriction)
	if (strlen ( $quizName ) >= 1 && strlen( $quizName ) <= 45) {
		$quizadmin->addQuizName ( $quizName, $quizKeyword );
	}
}
// return nothing?
?>