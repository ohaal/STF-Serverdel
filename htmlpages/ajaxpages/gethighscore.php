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
$scores = $quizadmin->getCorrectAnswers($quizid);
$ret = array();
$i=0;
foreach ($scores as $score) {
	$i++;
	$ret[$i]['teamid'] = $score->idteam;
	$ret[$i]['teamname'] = $score->teamname;
	$ret[$i]['score'] = intval($score->correct);
}
echo (json_encode($ret));

?>