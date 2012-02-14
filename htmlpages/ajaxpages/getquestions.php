<?php 
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
if (isset ($_GET['quizid'])) {
	$quizid = $_GET['quizid'];
	if(!is_numeric($quizid)) {
		die();
	}
	$questionsArray = $quizadmin->getAllQuestionsForQuiz ($quizid);

$ret = "";
$ret .= "[ ";
foreach ( $questionsArray as $key => $question ) {
		$ret .= '{"idquestions":' . $key . ', "questionnumber":' . $question ['questionnumber'] . ', "questiontext": "' . $question ['questiontext'] . '", "correctanswer": ' . $question ['correctanswer'];
		if (isset($question['answers']) && sizeof ( $question ['answers'] ) > 0) {
			$ret .=', "answers": [';
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
}
?>