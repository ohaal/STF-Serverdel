<?php
// TODO: Move messages to seperate config file, or get a spec

require_once ('sms.php');
require_once ('config.php');

class SMSReceiveHandler {
	
	private $config;
	
	public function __construct(){
		include("config.php");
		$this->config = $config;
	}
	

	public function handleSms($phonenumber, $smstext) {
		
		$smsReact = new smsReaction();
		
		if (is_null( $phonenumber )) {
			error_log('Phone number missing', 0);
			return;
		}
		if (is_null( $smstext )) {
			error_log('SMS text missing', 0);
			return;
		}
		
		// Account for and remove any accidental double (or more) spaces in message
		$smstext = preg_replace( '/\s{2,}/', ' ', $smstext );
		

		// Split SMS message into array for easier handling
		if (!$this->config["keywords_enabled"]) {
			$smsparam = explode( ' ', $smstext, 3 );
			array_unshift($smsparam, $this->config["keywords_default"]);
		}
		else {
			$smsparam = explode( ' ', $smstext, 4 );
		}
		if (count( $smsparam ) <= 1) {
			// TODO: Should we send message to sender about this?
			// $this->config['lang_no_unknownformat']
			error_log('Too few parameters - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
		}
		$keyword = strtolower($smsparam[1]);
	
		// Get the quiz id based on keyword (looks for active quiz with keyword)
		$quizid = $smsReact->getQuizIdByKeyword( $keyword );
		if ($quizid < 0) {
			// TODO: Should we send message to sender about this?
			// $this->config['lang_no_invalidkeyword']
			error_log('Invalid keyword: '.$keyword.' - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
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
		if ($smsparam[2] == 'lag' || $smsparam[2] == 'lagnavn') {
			if (!array_key_exists(3, $smsparam) || is_null( $smsparam[3] ) || empty( $smsparam[3] )) {
				$smsReact->sendMessage( $this->config['lang_no_noteamnamegiven'], $phonenumber );
				error_log('No team name given - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			$teamname = $smsparam[3];
			
			// Check if user took <lagnavn> too literally, and remove <> if he did
			if (substr($teamname, 0, 1) == '<' && substr($teamname, -1, 1) == '>') {
				$teamname = substr($teamname, 1, -1);
			}
			
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
				$smsReact->sendMessage( str_replace('$teamname$', $teamname, $this->config['lang_no_createdandsignedupforteam']), $phonenumber );
				error_log('New team ('.$teamname.') created and added new member to team. - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			else {
				$smsReact->sendMessage( str_replace('$teamname$', $teamname, $this->config['lang_no_signedupforteam']), $phonenumber );
				error_log('Added new member to team "'.$teamname.'" - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
		}
		// -> Format: <keyword> <question number> <answer alternative> || <keyword> <question number><answer alternative>
		// Examples: STF 1 a
		//           STF 3c
		else if (is_numeric( $smsparam[2] ) || $combined = preg_match('/^([0-9])+([a-eA-E])$/', $smsparam[2], $questionanswer)) {
			if (!$combined && !array_key_exists(3, $smsparam) ||
			(array_key_exists(3, $smsparam) && (is_null( $smsparam[3] ) || empty( $smsparam[3] ) || !ctype_alpha( $smsparam[3] ) || strlen( $smsparam[3] ) != 1))
			) {
				$smsReact->sendMessage( $this->config['lang_no_invalidansweralternativeprovided'], $phonenumber );
				error_log('Invalid answer alternative provided - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			
			if ($combined && !empty($questionanswer)) {
				$questionnumber = $questionanswer[1];
				$answer = $questionanswer[2];
			}
			else {
				$questionnumber = $smsparam[2];
				$answer = $smsparam[3];
			}
			
			// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
			$search  = array('a','b','c','d','e');
			$replace = array('1','2','3','4','5');
			$answernumber = str_replace($search, $replace, strtolower($answer));
			
			// Check if both question number and answer number are valid
			if (!$smsReact->isValidQuestionNumberAndAnswerNumber( $questionnumber, $answernumber, $quizid )) {
				$smsReact->sendMessage( $this->config['lang_no_invalidquestionnumberoranswer'], $phonenumber );
				error_log('Invalid question number ('.$questionnumber.') or answer ('.$answer.') - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			
			// Add answer to participant
			$smsReact->addAnswerToParticipant( $answernumber, $questionnumber, $phonenumber, $quizid );
			
			if ($teamid == 0) {
				// Team member not member of any team
				$smsReact->sendMessage( str_replace(array('$answer$', '$questionnumber$'), array(strtoupper($answer), $questionnumber), $this->config['lang_no_registeredanswerbutnoteam']), $phonenumber );
				error_log('Registered answer ('.$answer.'), but not member of team - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			else {
				// Team member of team
				$smsReact->sendMessage( str_replace(array('$answer$', '$questionnumber$'), array(strtoupper($answer), $questionnumber), $this->config['lang_no_registeredanswer']), $phonenumber );
				error_log('Registered answer ('.$answer.') - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
		}
		// Unknown format (unknown command)
		else {
			$smsReact->sendMessage( $this->config['lang_no_unknownformat'], $phonenumber );
			error_log('Unknown format! - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
		}

	}
}

?>
