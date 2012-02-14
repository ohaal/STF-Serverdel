<?php
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (!isset ($_POST['questionid'])) {
	//not a proper post. Ignore and die
	die();
}
$questionid = ($_POST['questionid']);
//might be NULL if it is a new question. If it is an edit, we will get a number here.
$questionnumber = $_POST['questionnumber'];
//might be NULL if new question
$questiontext = $_POST['questiontext'];
$quizid = $_POST['quizid'];
if (isset ($_POST['correctanswer'])) {
	$correctanswer = $_POST['correctanswer'];
} else {
	$correctanswer = NULL;
}
$answer = array();
$i=1;
while(isset($_POST['answer'.$i])) {
	$answer[$i] = $_POST['answer'.$i];
	$i++;
}

$quizadmin->addOrEditQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answer);
?>