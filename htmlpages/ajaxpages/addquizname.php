<?php
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_GET ['quizname'] )) {
	$quizName = $_GET ['quizname'];
	
	if (strlen ( $quizName ) > 0) {
		// Consider adding a check here for existing quizzes.
		$quizadmin->addQuizName ( $quizName );
	}
}
// return nothing?
?>