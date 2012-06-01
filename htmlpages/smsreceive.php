<?php
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
		
		// Account for and remove any accidental double (or more) whitespaces in message
		$smstext = preg_replace( '/\s{2,}/', ' ', $smstext );
		
		// Split SMS message into array for easier handling
		// If keywords enabled, format will be: STF <keyword> <remaining data>
		if ($this->config['keywords_enabled']) {
			$smsparam = explode( ' ', $smstext, 3 );
			$keyword = strtolower($smsparam[1]);
		}
		// If keywords not enabled, format will be: STF <remaining data>
		else {
			$smsparam = explode( ' ', $smstext, 2 );
			// Throw in the default keyword, so we don't have to make exceptions all over the code for this
			array_unshift($smsparam, $this->config['keywords_default']);
			$keyword = $this->config['keywords_default'];
		}
		if (count( $smsparam ) < 3) {
			$smsReact->sendMessage( $this->config['lang_no_unknownformat'], $phonenumber );
			error_log('Too few parameters - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
		}
		$remainingdata = $smsparam[2];

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
		if ($teamid == 0) {
			// Create a team for this participant
			// (we give each participant a team of their own, to avoid redoing ~the entire code because of removing team functionality)
			$teamname = $phonenumber;
			$teamid = $smsReact->getTeamIdByTeamName($teamname);
			// TeamId < 0	No team found with this name
			// TeamId > 0	Team found with ID
			if ($teamid < 0) {
				// Create team if team does not already exist
		 		$teamid = $smsReact->createTeam( $teamname );
			}
			// Associate participant with team
			$smsReact->addParticipantToTeam( $phonenumber, $quizid, $teamid );
		}

		// Use a regex to handle the remaining data in a best possible way
		// Remaining data can be formatted in these ways:
		// <question number> <answer alternative> || <question number><answer alternative> ||
		// <answer alternative> <question number> || <answer alternative><question number>
		// Examples: "STF 1 a" || "STF 3c" || "STF d2" || "STF b 4"
		// Simple explanation of regex used:
		// ^[\s.\'"`:<\/>;,-_]*?	Match any amount of the characters inside brackets at beginning of line, \s means any whitespace char (lazy)
		// ([1-9][0-9]*)			Capture numbers larger than 0 (first capture group)
		// [\s.\'"`:<\/>;,-_]*?		Match any amount of the characters inside brackets, \s means any whitespace char (lazy)
		// ([a-eA-E])				Capture a single character between A and E (match for both upper- and lower-case)
		// The other regex is pretty much the same, just with the letter first instead of the number
		if (preg_match('/^[\s.\'":<\/>;,-_]*?([1-9][0-9]*)[\s.\'"`:<\/>;,-_]*?([a-eA-E])/', $remainingdata, $questionanswer) ||
			preg_match('/^[\s.\'":<\/>;,-_]*?([a-eA-E])[\s.\'"`:<\/>;,-_]*?([1-9][0-9]*)/', $remainingdata, $answerquestion)) {
			
			if (!empty($questionanswer)) {
				$questionnumber = $questionanswer[1];
				$answer = $questionanswer[2];
			}
			else {
				$answer = $answerquestion[1];
				$questionnumber = $answerquestion[2];
			}
		
			// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
			$search  = array('a','b','c','d','e');
			$replace = array('1','2','3','4','5');
			$answernumber = str_replace($search, $replace, strtolower($answer));
			
			// Check if both question number and answer number are valid
			if (!$smsReact->isValidQuestionNumberAndAnswerNumber( $questionnumber, $answernumber, $quizid )) {
				$smsReact->sendMessage( str_replace(array('$answer$', '$questionnumber$'), array(strtoupper($answer), $questionnumber), $this->config['lang_no_invalidquestionnumberoranswer']), $phonenumber );
				error_log('Invalid question number ('.$questionnumber.') or answer ('.$answer.') - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;
			}
			
			// Add answer to participant
			$smsReact->addAnswerToParticipant( $answernumber, $questionnumber, $phonenumber, $quizid );
			
			// Reply success
			$smsReact->sendMessage( str_replace(array('$answer$', '$questionnumber$'), array(strtoupper($answer), $questionnumber), $this->config['lang_no_registeredanswer']), $phonenumber );
			error_log('Registered answer ('.$answer.') on question number '.$questionnumber.' - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
		}
		// Not captured by regex, user must have done something wrong, let's figure out what
		// Only submitting answer, only submitting question number, see common errors ("Unknown format" in log)
		else {
			// Match question number only?
			if (preg_match('/^[\s.\'"`:<\/>;,-_]*?[1-9][0-9]*/', $remainingdata)) {
				$smsReact->sendMessage( $this->config['lang_no_invalidansweralternativeprovided'], $phonenumber );
				error_log('Answer alternative invalid or missing - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;				
			}

			// Match answer only?
			if (preg_match('/^[\s.\'"`:<\/>;,-_]*?[a-eA-E][\s.\'":<\/>;,-_]+/', $remainingdata)) {
				$smsReact->sendMessage( $this->config['lang_no_invalidquestionnumberprovided'], $phonenumber );
				error_log('Question number invalid or missing - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
				return;				
			}
			
			// Did not recognize specific error, -> unknown format (unknown command)
			$smsReact->sendMessage( $this->config['lang_no_unknownformat'], $phonenumber );
			error_log('Unknown format! - Phonenumber: '.$phonenumber.' Text: "'.$smstext.'"', 0);
			return;
		}

	}
}

?>
