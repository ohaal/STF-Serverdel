<?
require_once ('db.php');
class quizAdmin {
	private $db;
	
	function __construct() {
		$this->db = new dbConnection();
	}
	
	function removeHtmlChars($string) {
		$ret = htmlspecialchars ($string);
		return $ret;
	}
	
	function getAllQuizNames() {
		return $this->db->getQuizNames();
	}
	
	function addQuizName($quizname, $quizkeyword) {
		$quizname = $this->removeHtmlChars($quizname);
		$quizkeyword = $this->removeHtmlChars($quizkeyword);
		return $this->db->addQuizName($quizname, $quizkeyword);
	}
	
	function getAllQuestionsForQuiz($quizid) {
		$questions = $this->db->getAllQuestionsForQuiz($quizid);
		return $questions;
	}
	
	function activateQuiz($quizid) {
		$this->db->setQuizState( $quizid, 1 );
	}
	
	function deactivateQuiz($quizid) {
		$this->db->setQuizState( $quizid, 0 );
	}
	
	function endQuiz($quizid) {
		$this->db->setQuizState( $quizid, 2 );
	}
	
	function getQuizState($quizid) {
		return $this->db->getQuizState($quizid);
	}
	
	function getQuizKeywordExistsAndActive($keyword) {
		$quizStates = $this->db->getQuizStatesByKeyword($keyword);
		foreach ($quizStates as $value) {
			if ($value == 1) { // 1 = active quiz
				return true;
			}
		}
		return false;
	}
	
	function getQuizKeyword($quizid) {
		return $this->db->getQuizKeyword($quizid);
	}
	
	function getQuestion($quizid, $questionnumber) {
		$question = $this->db->getQuestion($quizid, $questionnumber);
		return $question;
	}
	function addOrEditQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $answers) {
		$questiontext = $this->removeHtmlChars($questiontext);
		$ans = array();
		foreach ($answers as $key => $a) {
			$ans[$key]=$this->removeHtmlChars($a);
		}
		if (is_numeric($questionnumber)) {
			return $this->db->editQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $ans);
		} else {
			return $this->db->addQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $ans);
		}
	}
	function sortQuestions($quizid, $neworder) {
		// neworder should be an array of {questionorder, questionid (primary key)}
		$this->db->sortQuestions($quizid, $neworder);
	}
	function deleteQuestion($quizid, $questionid) {
		$this->db->deleteQuestion($quizid, $questionid);
	}
	
	function getCorrectAnswers($quizid, $correctanswersneeded=null) {
		$ret = $this->db->getCorrectAnswersForQuiz($quizid, $correctanswersneeded);
		return $ret;
	}
	
	function getTeamInfo($teamid, $quizid) {
		$ret = $this->db->getTeamInfo($teamid, $quizid);
		$phoneNumberList = array();
		foreach ($ret as $t) {
			$teamName = $t->teamname;
			$phoneNumberList[] = $t->phonenumber;
		}
		return array('teamname' => $teamName, 'phonenumbers' => $phoneNumberList);
	}
	
	function getTeamAnswers($teamid, $quizid) {
		return $this->db->getTeamAnswers($teamid, $quizid);
	}

	function setTeamName($teamid, $newname) {
		$newname = $this->removeHtmlChars($newname);
		return $this->db->setTeamName($teamid, $newname);
	}
}
?>