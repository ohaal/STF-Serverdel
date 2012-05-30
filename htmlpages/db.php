<?php
// TODO: mysql_real_escape_string()
// TODO: http://stackoverflow.com/questions/4752026/do-i-need-to-sanitize-input-if-using-prepared-php-mysql-queries
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
		$sql = "SELECT idquiz, quizname, state, keyword FROM quiz ORDER BY idquiz";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret [$row->idquiz] = array($row->quizname, $row->state, $row->keyword);
				}
			}
		} else {
			print_r($this->dbconn->error);
		}
		return $ret;
	}
	
	function addQuizName($quizName, $quizKeyword) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO quiz (quizname, keyword) VALUES (?, ?)")) {
			$stmt->bind_param('ss', $quizName, $quizKeyword);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function getQuizStatesByKeyword($keyword) {
		$quizstates = array();
		if ($stmt = $this->dbconn->prepare( "SELECT state FROM quiz WHERE keyword=?;" )) {
			$stmt->bind_param( 's', $keyword );
			$stmt->execute();
			$stmt->bind_result( $quizstate );
			while ( $stmt->fetch() ) {
				$quizstates[] = $quizstate;
			}
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $quizstates;
	}

	function getAllQuestionsForQuiz($quiz) {
		$ret = array ();
		if (!is_numeric($quiz)) {
			return $ret;
		}
		$sql = "SELECT questions.idquestion, questions.quizid as quizid, quiz.quizheader as quizheader, quiz.quizingress as quizingress, quiz.quizfooter as quizfooter, questions.questionnumber as questionnumber, questions.questiontext, questions.questionheading, questions.questioningress, questions.correctanswer, answers.quizid as aquizid, answers.answernumber, answers.answertext FROM questions LEFT JOIN (answers, quiz) ON (questions.quizid = answers.quizid AND questions.quizid = quiz.idquiz AND questions.idquestion = answers.questionid) WHERE questions.quizid = $quiz ORDER BY questions.questionnumber, answers.answernumber";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $q = $result->fetch_object () ) {
					$key = "".$q->quizid.".".$q->questionnumber.".".$q->idquestion;
					if (!array_key_exists($key, $ret)) {
						$ret[$key] = array('idquestion'=> $q->idquestion, 'quizid' => $q->quizid, 'quizheader' => $q->quizheader, 'quizingress' => $q->quizingress, 'quizfooter' => $q->quizfooter, 'questionnumber' => $q->questionnumber, 'questiontext' => $q->questiontext, 'questionheading' => $q->questionheading, 'questioningress' => $q->questioningress, 'correctanswer' => $q->correctanswer);
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
			printf("Prepared Statement Error: %s\n", $this->dbconn->error);
		}
	}
	
	function setQuizState($quizid, $state) {
		if ($stmt = $this->dbconn->prepare( "UPDATE quiz SET state=? WHERE idquiz=?;" )) {
			$stmt->bind_param( 'ii', $state, $quizid );
			$stmt->execute();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $this->dbconn->error );
		}
	}
	
	function setQuizPDFData($quizid, $quizheader, $quizingress, $quizfooter) {
		if ($stmt = $this->dbconn->prepare( "UPDATE quiz SET quizheader=?, quizingress=?, quizfooter=? WHERE idquiz=?;" )) {
			$stmt->bind_param( 'sssi', $quizheader, $quizingress, $quizfooter, $quizid );
			$stmt->execute();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $this->dbconn->error );
		}		
	}
	
	function setQuestionPDFData($quizid, $questionnumber, $questionheading, $questioningress) {
		if ($stmt = $this->dbconn->prepare( "UPDATE questions SET questionheading=?, questioningress=? WHERE quizid=? AND questionnumber=?;" )) {
			$stmt->bind_param( 'ssii', $questionheading, $questioningress, $quizid, $questionnumber );
			$stmt->execute();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $this->dbconn->error );
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
			printf( "Prepared Statement Error: %s\n", $this->dbconn->error );
		}
		return $quizstate;
	}
	
	function getQuizKeyword($quizid) {
		$quizkeyword = '';
		if ($stmt = $this->dbconn->prepare( "SELECT keyword FROM quiz WHERE idquiz=?;" )) {
			$stmt->bind_param( 'i', $quizid );
			$stmt->execute();
			$stmt->bind_result( $quizkeyword );
			$stmt->fetch();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $quizkeyword;
	}
	
	function getQuizIdByKeyword($keyword) {
		$quizid = -1;
		if ($stmt = $this->dbconn->prepare( "SELECT idquiz FROM quiz WHERE keyword=? AND state=1;" )) {
			$stmt->bind_param( 's', $keyword );
			$stmt->execute();
			$stmt->bind_result( $quizid );
			$stmt->fetch();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $quizid;
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
	
	function getCorrectAnswersForQuiz($quizid, $correctanswersneeded) {
		if (! (is_numeric ( $quizid ))) {
			die ();
		}
		$minimumcorrect = '';
		if (is_numeric($correctanswersneeded)) {
			$minimumcorrect = "HAVING correct >= $correctanswersneeded ";
		}
		$ret = array();
		$sql = "SELECT DISTINCT teams.idteam, teams.teamname, COUNT(DISTINCT teams.idteam, teams.teamname, questions.idquestion) AS correct ".
				"FROM teams, teammember, teamanswers, questions ".
				"WHERE (teammember.quizid=$quizid AND teams.idteam=teammember.teamid) AND (teamanswers.phonenumber=teammember.phonenumber AND teamanswers.questionid=questions.idquestion AND teamanswers.answer=questions.correctanswer) AND (questions.quizid=teammember.quizid) ".
				"GROUP BY teams.idteam ".
				$minimumcorrect.
				"ORDER BY correct DESC, teams.idteam, questions.questionnumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret[] = $row;
				}
			}
		}
		return $ret;
	}
	
	function getTeamInfo($teamid, $quizid) {
		if (! is_numeric ( $teamid ) || ! is_numeric ( $quizid )) {
			die ();
		}
		$ret = array();
		$sql = "SELECT DISTINCT teams.teamname, teammember.phonenumber FROM teams, teammember WHERE teams.idteam=$teamid AND teammember.teamid=teams.idteam AND teammember.quizid=$quizid;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret[] = $row;				
				}
			}
		}
		return $ret;
	}
	
	function getAllTeamNames() {
		
	}
	function getTeamNamesForQuiz($quiz) {	
		//return all teamnames which has sent at least one answer to a given quiz.		
	}
	function getTeamAnswers($teamid, $quizid) {
		$ret = array();
		if (! is_numeric ( $teamid ) || ! is_numeric ( $quizid )) {
			return $ret;
		}
		$sql = "SELECT questions.idquestion, questions.questionnumber, questions.correctanswer, teamanswers.answer FROM questions, teamanswers, teammember WHERE teamanswers.questionid = questions.idquestion AND questions.quizid=$quizid AND teammember.teamid=$teamid AND teamanswers.phonenumber=teammember.phonenumber ORDER BY questionnumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$ret[] = $row;
				}
			}
		}
		return $ret;
	}
	
	function isValidQuestionNumberAndAnswerNumber($questionnumber, $answernumber, $quizid) {
		if (! is_numeric ( $questionnumber ) || ! is_numeric ( $answernumber ) || ! is_numeric ( $quizid )) {
			return false;
		}
		$sql = "SELECT COUNT(*) AS matches ".
				"FROM questions, answers, quiz ".
				"WHERE quiz.idquiz=$quizid AND questions.quizid=quiz.idquiz AND answers.quizid=quiz.idquiz AND questions.idquestion=answers.questionid AND questions.questionnumber=$questionnumber AND answers.answernumber=$answernumber;";
		if ($result = $this->dbconn->query ( $sql )) {
			if ( $row = $result->fetch_object ()) {
				if (is_numeric($row->matches) && $row->matches == 1) {
					return true;
				}
			}
		}
		return false;
	}
	
	function setTeamName($teamid, $newname) {
		if ($stmt = $this->dbconn->prepare("UPDATE teams SET teamname=? WHERE idteam=?")) {
			$stmt->bind_param('si', $newname, $teamid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function createTeam($name) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO teams (teamname) VALUES (?)")) {
			$stmt->bind_param('s', $name);
			$stmt->execute();
			$stmt->close();
			return mysqli_insert_id($this->dbconn);
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
			return -1;
		}
	}
	
	function addTeamMemberAnswer($answernumber, $questionnumber, $phonenumber, $quizid) {
		$stmt = $this->dbconn->prepare(
			"INSERT INTO teamanswers (answer, phonenumber, questionid) ".
			"SELECT ?, ?, questions.idquestion ".
			"FROM questions ".
			"WHERE questionnumber=? AND quizid=?");
		if ($stmt) {
			$stmt->bind_param('isii', $answernumber, $phonenumber, $questionnumber, $quizid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}

	function getTeamIdByPhoneNumberAndQuizId($phonenumber, $quizid) {
		$teamid = -1;
		if ($stmt = $this->dbconn->prepare( "SELECT teamid FROM teammember WHERE phonenumber=? AND quizid=?;" )) {
			$stmt->bind_param( 'si', $phonenumber, $quizid );
			$stmt->execute();
			$stmt->bind_result( $teamid );
			$stmt->fetch();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $teamid;
	}
	
	function getTeamIdByTeamName($teamname) {
		$teamid = -1;
		if ($stmt = $this->dbconn->prepare( "SELECT idteam FROM teams WHERE teamname=?;" )) {
			$stmt->bind_param( 's', $teamname );
			$stmt->execute();
			$stmt->bind_result( $teamid );
			$stmt->fetch();
			$stmt->close();
		}
		else {
			printf( "Prepared Statement Error: %s\n", $stmt->error );
		}
		return $teamid;
	}
	
	function createTeamMember($phonenumber, $quizid, $teamid ) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO teammember (phonenumber, quizid, teamid) VALUES (?, ?, ?)")) {
			$stmt->bind_param('sii', $phonenumber, $quizid, $teamid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function setTeamMembership($phonenumber, $quizid, $teamid) {
		if ($stmt = $this->dbconn->prepare("UPDATE teammember SET teamid=? WHERE phonenumber=? AND quizid=?")) {
			$stmt->bind_param('isi', $teamid, $phonenumber, $quizid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
		}
	}
	
	function setMmsState($msgid, $state) {
		if ($stmt = $this->dbconn->prepare("UPDATE mms SET state=? WHERE msgid=?")) {
			$stmt->bind_param('ii', $state, $msgid);
			$stmt->execute();
			$stmt->close();
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
			return false;
		}
		return true;
	}
	
	function getMmsList($state) {
		$mmslist = array();
		$sql = "SELECT msgid, recvdate, text, imgpath, phonenumber FROM mms WHERE state=$state";
		if ($result = $this->dbconn->query ( $sql )) {
			if ($result->num_rows > 0) {
				while ( $row = $result->fetch_object () ) {
					$mmslist[] = $row; 
				}
			}		
		} else {
			print_r($this->dbconn->error);
		}
		return $mmslist;
	}
	
	function addMms($phonenumber, $message, $imgpath) {
		if ($stmt = $this->dbconn->prepare("INSERT INTO mms (phonenumber, text, imgpath) VALUES (?, ?, ?)")) {
			$stmt->bind_param('sss', $phonenumber, $message, $imgpath);
			$stmt->execute();
			$stmt->close();
			return mysqli_insert_id($this->dbconn);
		} else {
			printf("Prepared Statement Error: %s\n", $stmt->error);
			return -1;
		}
	}
}
?>
