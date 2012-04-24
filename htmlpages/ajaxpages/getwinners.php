<?php
// CALLED WHEN CHOOSING WINNER RANDOMLY BASED ON PARAMETERS
// Parameters from _GET:
// quizid				: int	: id of quiz
// correctanswersneeded	: int	: minimum amount of answers required to be a valid winner
// amountofwinners		: int	: how many winners possible, 1st, 2nd, 3rd and so on (less or equal to teams with enough correctanswersneeded)
// priomostcorrect		: bool	: should we prioritize teams with more correct answers for better rank, or choose randomly if teams have enough answers?
if (!isset( $_GET['quizid'] ) || !is_numeric( $_GET['quizid'] )) {
	// Invalid quizid
	die();
}
if (!isset( $_GET['correctanswersneeded'] ) || !is_numeric( $_GET['correctanswersneeded'] )) {
	// Invalid value for correctanswersneeded
	die();
}
if (!isset( $_GET['amountofwinners'] ) || !is_numeric( $_GET['amountofwinners'] )) {
	// Invalid value for amountofwinners
	die();
}
if (!isset( $_GET['priomostcorrect'] ) || ($_GET['priomostcorrect'] !== 'true' && $_GET['priomostcorrect'] !== 'false') ) {
	// Invalid value for priomostcorrect
	die();
}

$quizid = $_GET['quizid'];
$correctanswersneeded = $_GET['correctanswersneeded'];
$amountofwinners = $_GET['amountofwinners'];
$priomostcorrect = $_GET['priomostcorrect'] === 'true' ? true : false;

include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
$potentialwinners = $quizadmin->getCorrectAnswers( $quizid, $correctanswersneeded );


$sortedwinnerlist = array();
// Decide who is 1st place, 2nd place and so on based on how many winners we want
if ($priomostcorrect) {
	// Prioritize teams with more correct answers
	// Make some rules for how we want it sorted (we use a function to do the sorting)
	function winnerSort($a, $b) {
		if ($a->correct == $b->correct) {
			// We randomize if two teams have the same amount of correct answers
			return (mt_rand(1,2) > 1) ? -1 : 1;
		}
		return ($a->correct > $b->correct) ? -1 : 1;
	}
	usort($potentialwinners, 'winnerSort');
	// Array is now sorted based on who has the most correct answers, teams with same amount are placed randomly (around each other)
	// Limit the amount of winners and prepare output
	for ($i = 0; $i < $amountofwinners; $i++) {
		$teaminfo = $quizadmin->getTeamInfo($potentialwinners[$i]->idteam, $quizid);
		$sortedwinnerlist[] = array(
			'idteam'       => $potentialwinners[$i]->idteam,
			'teamname'     => $potentialwinners[$i]->teamname,
			'correct'      => $potentialwinners[$i]->correct,
			'phonenumbers' => $teaminfo['phonenumbers']
		);
	}
}
else {
	// Keeping it simple: Just pick random teams from list of winners and keep them unique
	$potentialwinnercount = count($potentialwinners);
	if ($potentialwinnercount < $amountofwinners) {
		echo 'Less potential winners than required amount of winners';
		die();
	}
	$used = array();
	while (true) {
		$rndpos = mt_rand(1, $potentialwinnercount)-1;
		if (!isset($used[$rndpos])) {
			$teaminfo = $quizadmin->getTeamInfo($potentialwinners[$rndpos]->idteam, $quizid);
			// First in array, first place!
			$sortedwinnerlist[] = array(
				'idteam'   => $potentialwinners[$rndpos]->idteam,
				'teamname' => $potentialwinners[$rndpos]->teamname,
				'correct'  => $potentialwinners[$rndpos]->correct,
				'phonenumbers' => $teaminfo['phonenumbers']
			);
			$used[$rndpos] = true;
			if (count($sortedwinnerlist) == $amountofwinners) {
				break;
			}
		}
	}
}

print(json_encode($sortedwinnerlist));
?>