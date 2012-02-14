<?php
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ( $_GET ['questionid'] )) {
	$questionid = $_GET ['questionid'];
	if (! is_numeric ( $questionid )) {
		die ();
	}
	$question = $quizadmin->getQuestion ( $questionid );

	$ret = "";
	$ret .= '{"idquestions":' . $question['idquestions'] . ', "questionnumber":' . $question ['questionnumber'] . ', "questiontext": "' . $question ['questiontext'] . '", "correctanswer": ' . $question ['correctanswer'];
	if (isset($question['answers']) && sizeof ( $question ['answers'] ) > 0) {
		$ret .= ', "answers": [';
		foreach ( $question ['answers'] as $answer ) {
			$ret .= '{"answernumber":' . $answer ['answernumber'] . ', "answertext": "' . $answer ['answertext'] . '"},';
		}
		$ret = substr ( $ret, 0, - 1 ); // remove last ","
		$ret .= ' ]';
	}
	$ret .= '}';
	print ($ret) ;
}
?>