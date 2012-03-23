<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
$neworder=array();
foreach ($_GET['q'] as $newpos => $item) {
	$a = explode(".", $item);
	$quizid=$a[0];
	$neworder[$newpos+1] = $a[2];
}
if (count($neworder) == 0 || !isset($quizid)) {
	die();
}

// Can only change question order if quiz state is inactive
if ($quizadmin->getQuizState($quizid) != '0') {
	die();
}
$quizadmin->sortQuestions($quizid, $neworder);
?>