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
	
	function addQuizName($quizname) {
		$quizname = $this->removeHtmlChars($quizname);
		return $this->db->addQuizName($quizname);
	}
	
	function getAllQuestionsForQuiz($quizid) {
		$questions = $this->db->getAllQuestionsForQuiz($quizid);
		return $questions;
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
}
?>