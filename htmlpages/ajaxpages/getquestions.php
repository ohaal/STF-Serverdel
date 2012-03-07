<?php
// Parameters from _POST:
// quizid			: int 	 : id of quiz to add question to
//
// Sample output:
//[
//   {
//      "idquestions":"1.1.1",
//      "quizid":1,
//      "questionnumber":1,
//      "questiontext":"some question",
//      "correctanswer":3,
//      "answers":[
//         {
//            "answernumber":1,
//            "answertext":"some answer"
//         },
//         {
//            "answernumber":2,
//            "answertext":"some other answer"
//         },
//         {
//            "answernumber":3,
//            "answertext":"some correct answer"
//         }
//      ]
//   }
//]
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (!isset ( $_GET ['quizid'] )) {
	die();
}
$quizid = $_GET ['quizid'];
if (! is_numeric ( $quizid )) {
	die ();
}
$questionsArray = $quizadmin->getAllQuestionsForQuiz ( $quizid );

// Generate our own JSON, because json_encode() does not do it the way we want
$ret = "";
$ret .= "[ ";
foreach ( $questionsArray as $key => $question ) {
	$ret .= '{"idquestions": "' . $key . '", "quizid":' . $question ['quizid'] . ', "questionnumber":' . $question ['questionnumber'] . ', "questiontext": "' . $question ['questiontext'] . '", "correctanswer": ' . $question ['correctanswer'];
	if (isset ( $question ['answers'] ) && sizeof ( $question ['answers'] ) > 0) {
		$ret .= ', "answers": [';
		foreach ( $question ['answers'] as $answer ) {
			$ret .= '{"answernumber":' . $answer ['answernumber'] . ', "answertext": "' . $answer ['answertext'] . '"},';
		}
		$ret = substr ( $ret, 0, - 1 ); // remove last ","
		$ret .= ' ]';
	}
	$ret .= '},';
}
$ret = substr ( $ret, 0, - 1 ); // remove last ","
$ret .= "]";

print ($ret) ;
?>