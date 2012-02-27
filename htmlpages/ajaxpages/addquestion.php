<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (!isset ($_POST['quizid'])) {
	//not a proper post. Ignore and die
	die();
}
$questionnumber = $_POST['questionnumber'];
//might be NULL if new question
$questiontext = $_POST['questiontext'];
$quizid = $_POST['quizid'];
if (isset ($_POST['correctanswer'])) {
	$correctanswer = $_POST['correctanswer'];
} else {
	$correctanswer = NULL;
}
$answers = array();
$i=1;
while(isset($_POST['answer'.$i])) {
	$answers[$i] = $_POST['answer'.$i];
	$i++;
}
$quizadmin->addOrEditQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $answers);
?>