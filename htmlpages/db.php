<?
class dbConnection() {
      function __construct() {
      	       if (!file_exists('dbdetails.php)) {
	       	  /* We don't have the dbdetails.php file 
		     This file should give the connection details)
		  */
		  print ("Missing connection details file\n");
		  print ("Create a file called 'dbdetails.php'. This file should contain the following:");
		  print ("<?
    class dbDetails() {
        public static $user = '<your db user>';      
        public static $passwd = '<your password>';
        public static $database= '<your database name>';
			}
?>");
		  
	       }


}

?>