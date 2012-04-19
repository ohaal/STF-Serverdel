<pre>
<?
// TODO: Move messages to seperate config file, or get a spec

require_once ('sms.php');
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
// -> Format: <keyword> <question number> <answer number>
else if (is_numeric( $smsparam[1] )) {
	if (is_null( $smsparam[2] ) || empty( $smsparam[2] ) || !is_numeric( $smsparam[2] )) {
		$smsReact->sendMessage( 'Du må angi et svarnummer!', $phonenumber );
		echo 'No answer number provided';
		die();
	}
	$questionnumber = $smsparam[1];
	$answernumber = $smsparam[2];
	
	// Check if both question number and answer number are valid
	if (!$smsReact->isValidQuestionNumberAndAnswerNumber( $questionnumber, $answernumber, $quizid )) {
		$smsReact->sendMessage( 'Du har oppgitt et ugyldig spørsmål- eller svarnummer!', $phonenumber );
		echo 'Invalid question or answer number';
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