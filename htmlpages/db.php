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
		$sql = "SELECT idquiz, quizname, state FROM quiz ORDER BY idquiz";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret [$row->idquiz] = array($row->quizname, $row->state);
				}
			}
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;
	}
	
	function addQuizName($quizName) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO quiz (quizname, state) VALUES (?, ?)")) {
			// bind_param only accepts variables...
			$state = 0; // 0 = inactive, all quizzes will start off as inactive
			$stmt->bind_param('si', $quizName, $state);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function getAllQuestionsForQuiz($quiz) {
		if (!is_numeric($quiz)) {
			die();
		}
		$ret = array ();
		$sql = "SELECT questions.idquestion, questions.quizid as quizid, questions.questionnumber as questionnumber, questions.questiontext, questions.correctanswer, answers.quizid as aquizid, answers.answernumber, answers.answertext FROM questions LEFT JOIN answers ON (questions.quizid = answers.quizid AND questions.idquestion = answers.questionid)  WHERE questions.quizid = $quiz ORDER BY questions.questionnumber, answers.answernumber";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $q = $result->fetch_object () ) {
					$key = "".$q->quizid.".".$q->questionnumber.".".$q->idquestion;
					if (!array_key_exists($key, $ret)) {
						$ret[$key] = array('idquestion'=> $q->idquestion,'quizid' =>$q->quizid, 'questionnumber' => $q->questionnumber, 'questiontext' => $q->questiontext, 'correctanswer' => $q->correctanswer);
					}
					if ($q->answernumber) {
						$ret[$key]['answers'][$q->answernumber] = array('answernumber' => $q->answernumber, 'answertext' => $q->answertext);
					}
				}
			}		
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;
	}
	
	function getQuestion($quizid, $questionnumber) {
		if (!is_numeric($quizid) || ! is_numeric($questionnumber)) {
			die();
		}
		$sql = "SELECT questions.idquestion, questions.quizid as quizid, questions.questionnumber as questionnumber, questions.questiontext, questions.correctanswer, answers.quizid as aquizid, answers.questionid as aquestionid, answers.answernumber, answers.answertext FROM questions LEFT JOIN answers ON (questions.quizid = answers.quizid AND questions.idquestion = answers.questionid) WHERE questions.quizid = $quizid AND questions.questionnumber = $questionnumber ORDER BY answers.answernumber";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $q = $result->fetch_object () ) {
					if (!isset($ret)) {
						$ret = array('idquestion'=> $q->idquestion, 'quizid' =>$q->quizid, 'questionnumber' => $q->questionnumber, 'questiontext' => $q->questiontext, 'correctanswer' => $q->correctanswer);
					}
					if ($q->answernumber) {
						$ret['answers'][$q->answernumber] = array('answernumber' => $q->answernumber, 'answertext' => $q->answertext);
					}
				}
			}
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;	
	}
	
	function editQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $answers) {
		if ($stmt = $this->dbconn->prepare("UPDATE questions SET questiontext=?, correctanswer=? WHERE quizid=? AND questionnumber=?;")) {
			$stmt->bind_param('siii', $questiontext,$correctanswer,$quizid, $questionnumber);
			$stmt->execute();
			$stmt->close();
			$this->addAnswers($quizid, $questionnumber, $answers);
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function setQuizState($quizid, $state) {
		if ($stmt = $this->dbconn->prepare( "UPDATE quiz SET state=? WHERE idquiz=?;" )) {
			$stmt->bind_param( 'ii', $state, $quizid );
			$stmt->execute();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
	}
	
	function getQuizState($quizid) {
		$quizstate = 0;
		if ($stmt = $this->dbconn->prepare( "SELECT state FROM quiz WHERE idquiz=?;" )) {
			$stmt->bind_param( 'i', $quizid );
			$stmt->execute();
			$stmt->bind_result( $quizstate );
			$stmt->fetch();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $quizstate;
	}
	
	function addQuestion($quizid, $questionnumber, $questiontext, $correctanswer, $answers) {
		$max=0;
		if (!$correctanswer) {
			$correctanswer =0;
		}
		if (!$questiontext || $questiontext == "") {
			die();
		}
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
			$this->addAnswers($quizid, $questionnumber, $answers);
		} else {
			printf("Prepared Statement Error: %s\n", $this->dbconn->error);
		}
	}
	
	function getQuestionid($quizid, $questionnumber) {
		// get the question id (autoincremented int)
		$idquestion = - 1;
		if ($stmt = $this->dbconn->prepare ( "SELECT idquestion FROM questions WHERE quizid=? AND questionnumber=?" )) {
			$stmt->bind_param ( 'ii', $quizid, $questionnumber );
			$stmt->execute ();
			$stmt->bind_result ( $idquestion );
			$stmt->fetch ();
			$stmt->close ();
		}
		
		return $idquestion;
	} 
	
	function addAnswers($quizid, $questionnumber, $answers) {
		$idquestion = $this->getQuestionid($quizid, $questionnumber);
		
		// remove all answers first. Simpler this way.
		$this->deleteAnswersForQuestion($quizid, $idquestion);
		foreach ($answers as $key => $a) {
			if ($a != '') {
				if ($stmt = $this->dbconn->prepare ( "INSERT INTO answers (quizid, questionid, answernumber, answertext) VALUES (?,?,?,?);" )) {
					$stmt->bind_param ( 'iiis', $quizid, $idquestion, $key, $a );
					$stmt->execute ();
					$stmt->close ();
				} else {
					printf("Prepared Statement Error: %s\n", $stmt->error);
				}
			}
		}
	}
	
	function sortQuestions($quizid, $neworder) {
		if (!is_numeric($quizid)) {
			die();
		}
		foreach ($neworder as $qnumber => $primarykey) {
			if ($stmt = $this->dbconn->prepare("UPDATE questions SET questionnumber=? WHERE quizid=? AND idquestion=?;")) {
				$stmt->bind_param( 'iii', $qnumber, $quizid, $primarykey);
				$stmt->execute();
				$stmt->close();
			} else {
				printf("Prepared Statement Error: %s\n", $stmt->error);
			}
		}
	}
	
	function deleteQuestion($quizid, $questionid) {
		//We use questionid instead of questionnumber here, more secure this way, I guess.
		if(!(is_numeric($quizid)) || !(is_numeric($questionid))) {
			die();
		}
		$this->deleteAnswersForQuestion($quizid, $questionid);
		if ($stmt = $this->dbconn->prepare ( "DELETE FROM questions WHERE quizid=? AND idquestion=?;" )) {
			$stmt->bind_param('ii', $quizid, $questionid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf ( "Prepared Statement Error: %s\n", $stmt->error );
		}
		//After deletion, we need to reorder the remaining questions
		$sql = "SELECT idquestion, questionnumber FROM questions WHERE quizid=$quizid ORDER BY questionnumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				$qn = 0;
				while ( $row = $result->fetch_object () ) {
					$qn ++;
					$idquestion = $row->idquestion;
					$sql = "UPDATE questions SET questionnumber=$qn WHERE idquestion=$idquestion;";
					$this->dbconn->query ($sql);
				}
			}
		} else {
			print_r($this->dbconn->error);
		}		
	}
	
	function deleteAnswersForQuestion($quizid, $questionid) {
		if (! (is_numeric ( $quizid )) || ! (is_numeric ( $questionid ))) {
			die ();
		}
		if ($stmt = $this->dbconn->prepare ( "DELETE FROM answers WHERE (quizid=? AND questionid=?)" )) {
			$stmt->bind_param ( 'ii', $quizid, $questionid );
			$stmt->execute ();
			$stmt->close ();
		} else {
			printf ( "Prepared Statement Error: %s\n", $stmt->error );
		}
	}
	
	function getCorrectAnswersForQuiz($quizid) {
		if (! (is_numeric ( $quizid ))) {
			die ();
		}
		$ret = array();
		$sql = "SELECT DISTINCT users.idusers, users.username, users.phonenumber, COUNT(DISTINCT users.idusers, users.username, users.phonenumber, questions.idquestion) as correct FROM useranswers, users, questions WHERE questions.quizid= $quizid AND useranswers.userid = users.idusers AND (useranswers.answer = questions.correctanswer AND useranswers.questionid = questions.idquestion) GROUP BY users.idusers ORDER BY correct DESC, users.idusers, questions.questionnumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret[] = $row;
				}
			}
		}
		return $ret;
	}
	
	function getTeamInfo($teamid) {
		if (! is_numeric ( $teamid )) {
			die ();
		}
		$sql = "SELECT username, phonenumber FROM users WHERE idusers=$teamid;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				$row = $result->fetch_object ();
				return $row;
			}
		}
	}
	
	function getAllTeamNames() {
		
	}
	function getTeamNamesForQuiz($quiz) {	
		//return all teamnames which has sent at least one answer to a given quiz.		
	}
	function getTeamAnswers($teamid, $quizid) {
		if (! is_numeric ( $teamid ) || ! is_numeric ( $quizid )) {
			die ();
		}
		$ret = array();
		$sql = "SELECT questions.idquestion, questions.questionnumber, questions.correctanswer, useranswers.answer FROM questions, useranswers WHERE useranswers.questionid = questions.idquestion AND questions.quizid=$quizid AND useranswers.userid=$teamid ORDER BY questionnumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret[] = $row;
				}
			}
		}
		return $ret;
	}
	
	function setTeamName($teamid, $newname) {
		if ($stmt = $this->dbconn->prepare("UPDATE users SET username=? WHERE idusers=?")) {
			$stmt->bind_param('si', $newname, $teamid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $mysqli->error);
		}
	}
}
?>
