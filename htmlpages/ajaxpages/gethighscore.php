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
foreach ($scores as $score) {
	$ret[$score->idusers]['username'] = $score->username;
	$ret[$score->idusers]['phonenumber'] = $score->phonenumber;
	if (!isset ($ret[$score->idusers]['score'])) {
		$ret[$score->idusers]['score'] = 0; 
	}
	$ret[$score->idusers]['score']++;
}

echo (json_encode($ret));

?>