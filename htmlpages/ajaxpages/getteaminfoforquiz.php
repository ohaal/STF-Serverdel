<?php

function questionSort($arr1, $arr2) {
	$a= intval($arr1['questionnumber']);
	$b= intval($arr2['questionnumber']);
	if ($a == $b) {
		return 0;
	}
	return ($a < $b) ? -1 : 1;
}


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

if (isset ( $_GET ['teamid'] )) {
	$teamid = $_GET ['teamid'];
	if (! is_numeric ( $teamid )) {
		die ();
	}
} else {
	die ();
}

$ret = array();

$info = $quizadmin->getTeamInfo($teamid);
$ret['info']['teamname'] = $info->teamname;
$ret['info']['phonenumber'] = $info->phonenumber;

$questions = $quizadmin->getAllQuestionsForQuiz($quizid);
$answerarray = array();
foreach ($questions as $q) {
	$answerarray[$q["idquestion"]]= $q;
}
$teamanswers = $quizadmin->getTeamAnswers($teamid, $quizid);
foreach ($teamanswers as $a) {
	$answerarray[$a->idquestion]['teamanswers'][]= intval($a->answer);
}
usort ($answerarray, "questionSort" );

$ret['answersarray'] = $answerarray;

print(json_encode($ret));
?>