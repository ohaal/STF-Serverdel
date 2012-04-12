<?php
// CALLED WHEN ADDING A NEW QUESTION TO QUIZ
// Parameters from _POST:
// quizid			: int 	 : id of quiz to add question to
// questiontext 	: string : the question itself
// questionnumber 	: int	 : the question number (used for sort)
// correctanswer 	: int	 : the correct answer number
// answerX 			: string : the answers
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin();
if (!isset( $_POST['quizid'] )) {
	//not a proper post. Ignore and die
	die();
}
$quizid = $_POST['quizid'];
$questionnumber = $_POST['questionnumber']; //might be NULL if new question
$questiontext = $_POST['questiontext'];

// Question text must be between 1 and 200 chars
if (strlen( $questiontext ) > 200 || strlen( $questiontext ) < 1) {
	die();
}

// Can only add question if quiz state is inactive
if ($quizadmin->getQuizState( $quizid ) != '0') {
	die();
}

if (isset( $_POST['correctanswer'] )) {
	$correctanswer = $_POST['correctanswer'];
}
else {
	$correctanswer = NULL;
}

// First answer must be populated 
if (!isset( $_POST['answer1'] ) || strlen( $_POST['answer1'] ) < 1) {
	die();
}
  
$answers = array();
$i = 1;
while (isset( $_POST['answer' . $i] )) {
	$answers[$i] = $_POST['answer' . $i];
	// No answer can be longer than 200 characters
	if (strlen( $answers[$i] ) > 200) {
		die();
	}
	// If answer is marked as correct, it needs to have some content
	if ($i == $correctanswer && strlen( $answers[$i] ) < 1 ) {
		die();
	}
	$i++;
}

// Correct answer can not be higher than the amount of answers provided
if ($correctanswer > ($i - 1)) {
	die();
}

$quizadmin->addOrEditQuestion( $quizid, $questionnumber, $questiontext, $correctanswer, $answers );
?>