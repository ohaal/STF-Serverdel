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
if (isset ( $_GET ['questionnumber'] )) {
	$questionnumber = $_GET ['questionnumber'];
	if (! is_numeric ( $questionnumber )) {
		die ();
	}
} else {
	die ();
}

$question = $quizadmin->getQuestion ( $quizid, $questionnumber );

$ret = "";
$ret .= '{"quizid":' . $question ['quizid'] . ', "questionnumber":' . $question ['questionnumber'] . ', "questiontext": "' . $question ['questiontext'] . '", "correctanswer": ' . $question ['correctanswer'];
if (isset ( $question ['answers'] ) && sizeof ( $question ['answers'] ) > 0) {
	$ret .= ', "answers": [';
	foreach ( $question ['answers'] as $answer ) {
		$ret .= '{"answernumber":' . $answer ['answernumber'] . ', "answertext": "' . $answer ['answertext'] . '"},';
	}
	$ret = substr ( $ret, 0, - 1 ); // remove last ","
	$ret .= ' ]';
}
$ret .= '}';
print ($ret) ;
?>