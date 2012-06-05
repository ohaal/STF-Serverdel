<?php
require_once ('db.php');

class mmsReaction {
	private $db;
	private $config;
	
	function __construct() {
		$this->db = new dbConnection();
		require('config.php');
		$this->config = $config;
	}
	
	function getAccepted() {
		return $this->db->getMmsList(1);
	}
	function getDeclined() {
		return $this->db->getMmsList(2);
	}
	function getQueued() {
		return $this->db->getMmsList(0);
	}
	function setAccepted($msgid) {
		return $this->db->setMmsState($msgid, 1);
	}
	function setDeclined($msgid) {
		return $this->db->setMmsState($msgid, 2);
	}
	function addMms($phonenumber, $message, $imgpath) {
		// Add to database
		$msgid = $this->db->addMms($phonenumber, $message, $imgpath);
		
		// Tell MMSadmin there is a new MMS available by poking mmsadminserver
		// We keep the poke as simple as possible so we don't have to implement a PHP WebSocket Client aswell
		fclose(fsockopen($this->config['ws_ip_lan_bind'], $this->config['ws_port'], $errno, $errstr, 2));

		// Return increment ID in DB
		return $msgid;
	}
}