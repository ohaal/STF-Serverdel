<pre>
<?php
// TODO: Move messages to seperate config file, or get a spec

require_once ('sms.php');
require_once ('config.php');

$smsReact = new smsReaction();

// TODO: Temporary input data until we know exactly what input we will receive
$smstext = $_GET['text'];
$phonenumber = '87654321';

if (is_null( $phonenumber )) {
	echo 'Phone number missing';
	die();
}
if (is_null( $smstext )) {
	echo 'SMS text missing';
	die();
}

// Account for and remove any accidental double (or more) spaces in message
$smstext = preg_replace( '/\s{2,}/', ' ', $smstext );

// Split SMS message into array for easier handling
$smsparam = explode( ' ', $smstext, 3 );
if (!$keywords_enabled) {
	array_unshift($smsparam, $keywords_default);
}
var_dump($smsparam);
if (count( $smsparam ) <= 1) {
	// TODO: Should we send message to sender about this?
	echo 'Too few parameters';
	die();
}
$keyword = $smsparam[0];

// Get the quiz id based on keyword (looks for active quiz with keyword)
$quizid = $smsReact->getQuizIdByKeyword( $keyword );
if ($quizid < 0) {
	// TODO: Should we send message to sender about this?
	echo 'Invalid keyword.';
	die();
}

$teamid = $smsReact->getTeamIdByPhoneNumberAndQuizId( $phonenumber, $quizid );
// TeamId < 0	no member found
// TeamId == 0	member found, but not member of any team
// TeamId > 0	member found and member of team
if ($teamid < 0) {
	// Create quiz participant (team member with no team)
	$smsReact->createParticipant( $phonenumber, $quizid );
	$teamid = 0;
}

$combined = false;

// Expected SMS text formats (we will act differently based on the format):
// -> Format: <keyword> lag <teamname> || <keyword> lagnavn <teamname>
if ($smsparam[1] == 'lag' || $smsparam[1] == 'lagnavn') {
	if (is_null( $smsparam[2] ) || empty( $smsparam[2] )) {
		$smsReact->sendMessage( 'Du må angi et lagnavn!', $phonenumber );
		echo 'No team name given';
		die();
	}
	$teamname = $smsparam[2];
	
	$newTeamCreated = false;
	
	$teamid = $smsReact->getTeamIdByTeamName( $teamname );
	// TeamId < 0	no team found with this name
	// TeamId > 0	team found
	if ($teamid < 0) {
		$newTeamCreated = true;
		$teamid = $smsReact->createTeam( $teamname );
	}
	// Associate phone number with team (connects all current answers by this phone number with team)
	$smsReact->addParticipantToTeam( $phonenumber, $quizid, $teamid );
	if ($newTeamCreated) {
		$smsReact->sendMessage( 'Laget "' . $teamname . '" er nå opprettet og du er påmeldt dette laget!', $phonenumber );
	}
	else {
		$smsReact->sendMessage( 'Du er nå påmeldt laget "' . $teamname . '"!', $phonenumber );
	}
}
// -> Format: <keyword> <question number> <answer alternative> || <keyword> <question number><answer alternative>
// Examples: STF 1 a
//           STF 3c
else if (is_numeric( $smsparam[1] ) || $combined = preg_match('/^([0-9])+([a-eA-E])$/', $smsparam[1], $questionanswer)) {
	if (!$combined && !array_key_exists(2, $smsparam) ||
	(array_key_exists(2, $smsparam) && (is_null( $smsparam[2] ) || empty( $smsparam[2] ) || !ctype_alpha( $smsparam[2] ) || strlen( $smsparam[2] ) != 1))
	) {
		$smsReact->sendMessage( 'Du må angi et gyldig svaralternativ!', $phonenumber );
		echo 'Invalid answer provided';
		die();
	}
	
	if ($combined && !empty($questionanswer)) {
		$questionnumber = $questionanswer[1];
		$answer = $questionanswer[2];
	}
	else {
		$questionnumber = $smsparam[1];
		$answer = $smsparam[2];
	}
	
	// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
	$search  = array('a','b','c','d','e');
	$replace = array('1','2','3','4','5');
	$answernumber = str_replace($search, $replace, strtolower($answer));
	
	// Check if both question number and answer number are valid
	if (!$smsReact->isValidQuestionNumberAndAnswerNumber( $questionnumber, $answernumber, $quizid )) {
		$smsReact->sendMessage( 'Du har oppgitt et ugyldig spørsmål- eller svaralternativ!', $phonenumber );
		echo 'Invalid question number or answer';
		die();
	}
	
	// Add answer to participant
	$smsReact->addAnswerToParticipant( $answernumber, $questionnumber, $phonenumber, $quizid );
	
	if ($teamid == 0) {
		// Team member not member of any team
		$smsReact->sendMessage( 'Ditt svar er registrert, men du er ikke påmeldt noe lag. For å melde deg på et lag eller etablere nytt send SMS til...', $phonenumber );
	}
	else {
		// Team member of team
		$smsReact->sendMessage( 'Ditt svar er registrert!', $phonenumber );
	}
}
// Unknown format (unknown command)
else {
	echo 'Unknown format!';
	die();
}

?>
</pre>