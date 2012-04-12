<?
require_once ('db.php');
class smsReaction {
	private $db;
	
	// TODO: sanitize!
	function __construct() {
		$this->db = new dbConnection();
	}
	
	function getQuizIdByKeyword($keyword) {
		return $this->db->getQuizIdByKeyword( $keyword );
	}
	
	function getTeamIdByPhoneNumberAndQuizId($phonenumber, $quizid) {
		return $this->db->getTeamIdByPhoneNumberAndQuizId( $phonenumber, $quizid );
	}
	
	function createParticipant($phonenumber, $quizid, $teamid = 0) {
		$this->db->createTeamMember( $phonenumber, $quizid, $teamid );
	}
	
	function getTeamIdByTeamName($teamname) {
		return $this->db->getTeamIdByTeamName( $teamname );
	}
	
	function createTeam($teamname) {
		return $this->db->createTeam( $teamname );
	}
	
	function sendMessage($message, $phonenumber) {
		// TODO: Send message somehow!
	}
	
	function addParticipantToTeam($phonenumber, $quizid, $teamid) {
		return $this->db->setTeamMembership( $phonenumber, $quizid, $teamid );
	}
	
	function addAnswerToParticipant( $answernumber, $questionnumber, $phonenumber, $quizid ) {
		$this->db->addTeamMemberAnswer( $answernumber, $questionnumber, $phonenumber, $quizid );
	}
}
?>