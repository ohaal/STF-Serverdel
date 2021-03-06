<?php
// Parameters from _GET:
// quizid			: int 	 : id of quiz to add question to
//
// Sample output:
//[
//   {
//      "idquestions":"1.1.1",
//      "quizid":1,
//      "quizheader":"some quizheader",
//      "quizingress":"some quizingress,
//      "quizfooter":"some quizfooter,
//      "questionnumber":1,
//      "questiontext":"some question",
//      "questionheading":"some heading",
//      "questioningress":"some ingress",
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
// Arrays to solve issues with special characters / new lines
$search = array("\\", '/', '"', "\b", "\t", "\n", "\f", "\r", "\u");
$replace = array("\\\\", "\\/", "\\".'"', "\\b", "\\t", "\\n", "\\f", "\\r", "\\u");
foreach ( $questionsArray as $key => $question ) {
	$ret .= '{"idquestions": "' . $key . '",'.
		' "quizid":' . $question ['quizid'] . ','.
		' "quizheader": "' . str_replace($search, $replace, $question ['quizheader']) . '",'.
		' "quizingress": "' . str_replace($search, $replace, $question ['quizingress']) . '",'.
		' "quizfooter": "' . str_replace($search, $replace, $question ['quizfooter']) . '",'.
		' "questionnumber":' . $question ['questionnumber'] . ','.
		' "questiontext": "' . str_replace($search, $replace, $question ['questiontext']) . '",'.
		' "questionheading": "' . str_replace($search, $replace, $question ['questionheading']) . '",'.
		' "questioningress": "' . str_replace($search, $replace, $question ['questioningress']) . '",'.
		' "correctanswer": ' . $question ['correctanswer'];
	if (isset ( $question ['answers'] ) && sizeof ( $question ['answers'] ) > 0) {
		$ret .= ', "answers": [';
		foreach ( $question ['answers'] as $answer ) {
			$ret .= '{"answernumber":' . $answer ['answernumber'] . ', "answertext": "' . str_replace($search, $replace, $answer ['answertext']) . '"},';
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