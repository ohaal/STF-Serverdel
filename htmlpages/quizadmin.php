<?
require_once ('db.php');
class quizAdmin {
	private $db;
	
	function __construct() {
		$this->db = new dbConnection();
	}
	
	function getAllQuizNames() {
		return $this->db->getQuizNames();
	}
	
	function addQuizName($quizname) {
		return $this->db->addQuizName($quizname);
	}
	
	function getAllQuestionsForQuiz($quizid) {
		$questions = $this->db->getAllQuestionsForQuiz($quizid);
		return $questions;
	}
	
	function getQuestion($questionid) {
		$question = $this->db->getQuestion($questionid);
		return $question;
	}
	function addOrEditQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answer) {
		if (is_numeric($questionid)) {
			return $this->db->editQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answer);
		} else {
			return $this->db->addQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answer);
		}
	}
}
?>