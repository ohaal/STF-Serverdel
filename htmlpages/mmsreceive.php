<?php
require_once ('sms.php');
require_once ('config.php');

class MMSReceiveHandler {
	
	private $config;
	
	public function __construct(){
		include("config.php");
		$this->config = $config;
	}
	

	public function handleMms($phonenumber, $subject, $data) {
		
		// TODO: Implement MMS handling

	}
}

?>
