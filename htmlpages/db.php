<?
class dbConnection {
	private $dbconn;
	
	function __construct() {
		$passwd = '';
		$user = '';
		$database = '';
		$host = '';
		try {
			require_once ('dbdetails.php');
			$passwd = dbDetails::$passwd;
			$user = dbDetails::$user;
			$database = dbDetails::$database;
			$host = dbDetails::$host;
		} catch ( Exception $e ) {
			print ('Missing connection details file' . "\n") ;
			print ('Create a file called \'dbdetails.php\'. This file should contain the following:' . "\n") ;
			print ('<?' . "\n") ;
			print ('class dbDetails {' . "\n") ;
			print ('    public static $user = \'<your db user>\';' . "\n") ;
			print ('    public static $passwd = \'<your password>\';' . "\n") ;
			print ('    public static $database= \'<your database name>\';' . "\n") ;
			print ('    public static $host=\'<host>\';' . "\n") ;
			print ('}' . "\n") ;
			print ('?>' . "\n") ;
			die ();
		}
		$this->dbconn = new mysqli ( $host, $user, $passwd, $database );
		if ($this->dbconn->connect_error) {
			die ( 'Connect Error (' . $this->dbconn->connect_errno . ') ' . $this->dbconn->connect_error );
		}
		
	}
	
	function __destruct() {
		if ($this->dbconn) {
			$this->dbconn->close();
		}
	}
	
	function getQuizNames () {
		$ret = array ();
		$sql = "SELECT idquiz, quizname FROM quiz ORDER BY idquiz";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret [$row->idquiz] = $row->quizname;
				}
			}
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;
	}
	
	function addQuizName($quizName) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO quiz (quizname) values(?)")) {
			$stmt->bind_param('s', $quizName);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $mysqli->error);
		}
	}
	
	function getAllQuestionsForQuiz($quiz) {
		if (!is_numeric($quiz)) {
			die();
		}
		$ret = array ();
		$sql = "SELECT questions.idquestions, questions.quizid, questions.questionnumber, questions.questiontext, questions.correctanswer, answers.idanswers, answers.questionid, answers.answernumber, answers.answertext FROM questions LEFT JOIN answers ON questions.idquestions = answers.questionid  WHERE questions.quizid = $quiz ORDER BY questions.questionnumber, answers.answernumber";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$q= $row;
					if (!array_key_exists($q->idquestions, $ret)) {
						$ret[$q->idquestions] = array('idquestions' =>$q->idquestions, 'questionnumber' => $q->questionnumber, 'questiontext' => $q->questiontext, 'correctanswer' => $q->correctanswer);
					}
					if ($q->answernumber) {
						$ret[$q->idquestions]['answers'][$q->idanswers] = array('answernumber' => $q->answernumber, 'answertext' => $q->answertext);
					}
				}
			}		
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;
	}
	
	function getQuestion($questionid) {
		if (!is_numeric($questionid)) {
			die();
		}
		$ret = array ();
		$sql = "SELECT questions.idquestions, questions.quizid, questions.questionnumber, questions.questiontext, questions.correctanswer, answers.idanswers, answers.questionid, answers.answernumber, answers.answertext FROM questions LEFT JOIN answers ON questions.idquestions = answers.questionid WHERE questions.idquestions = $questionid ORDER BY answers.answernumber";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$q= $row;
					if (!array_key_exists('idquestions', $ret)) {
						$ret = array('idquestions' =>$q->idquestions, 'questionnumber' => $q->questionnumber, 'questiontext' => $q->questiontext, 'correctanswer' => $q->correctanswer);
					}
					if ($q->answernumber) {
						$ret['answers'][$q->idanswers] = array('answernumber' => $q->answernumber, 'answertext' => $q->answertext);
					}
				}
			}
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;	
	}
	
	function editQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answers) {
		if ($stmt = $this->dbconn->prepare("UPDATE questions SET questiontext=?, correctanswer=?, questionnumber=?, quizid=? WHERE idquestions=?;")) {
			$stmt->bind_param('siiii', $questiontext,$correctanswer,$questionnumber,$quizid,$questionid);
			$stmt->execute();
			$stmt->close();
			$this->addAnswers($questionid, $answers);
		} else {
			printf("Prepared Statement Error: %s\n", $mysqli->error);
		}
	}
	
	function addQuestion($quizid, $questionid, $questionnumber, $questiontext, $correctanswer, $answers) {
		$max=0;
		if ($stmt = $this->dbconn->prepare("SELECT MAX(questionnumber) FROM questions WHERE quizid = ?;")) {
			$stmt->bind_param('i', $quizid);
			$stmt->execute();
			$stmt->bind_result($max);
			$stmt->fetch();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
		if (!$max) {
			$max=0;
		}
		$questionnumber = $max+1;
		if ($stmt = $this->dbconn->prepare("INSERT INTO questions (quizid, questionnumber, questiontext, correctanswer) VALUES (?,?,?,?);")) {
			$stmt->bind_param('iisi', $quizid,$questionnumber,$questiontext,$correctanswer);
			$stmt->execute();
			$stmt->close();
			$questionid = $this->dbconn->insert_id;
			$this->addAnswers($questionid, $answers);
		} else {
			printf("Prepared Statement Error: %s\n", $this->dbconn->error);
		}
	}
	
	function addAnswers($questionid, $answers) {
		// remove all answers first. Simpler this way.
		print_r($questionid);
		print_r($answers);
		if ($stmt = $this->dbconn->prepare ( "DELETE FROM answers WHERE questionid=?" )) {
			$stmt->bind_param ( 'i', $questionid );
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
		foreach ($answers as $key => $a) {
			if ($a != '') {
				if ($stmt = $this->dbconn->prepare ( "INSERT INTO answers (questionid, answernumber, answertext) VALUES (?,?,?);" )) {
					$stmt->bind_param ( 'iis', $questionid, $key, $a );
					$stmt->execute ();
					$stmt->close ();
				} else {
					printf("Prepared Statement Error: %s\n", $stmt->error);
				}
			}
		}
	}
	function getAlternativesForQuestion($question) {

	}
	function getAllTeamNames() {
		
	}
	function getTeamNamesForQuiz($quiz) {	
		
	}
	function getAnswersForTeam($team, $quiz) {
	
	}
}
?>
