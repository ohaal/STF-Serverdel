<?
class dbConnection {
	private $dbconn;
	
	function __construct() {
		if (! file_exists ( 'dbdetails.php' )) {
			/*
			 * We do not have the dbdetails.php file. This file should give the
			 * connection details
			 */
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
			print ('Could not find the db details.') ;
			die ();
		}
		$this->dbconn = new mysqli ( $host, $user, $passwd, $database );
		if ($this->dbconn->connect_error) {
			die ( 'Connect Error (' . $this->dbconn->connect_errno . ') ' . $this->dbconn->connect_error );
		}
		
	}
	
	function __destruct() {
		$this->dbconn->close();
	}
}

$dbC = new dbConnection ();

?>
