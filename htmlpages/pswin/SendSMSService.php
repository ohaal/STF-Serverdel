<?php

require_once('PSWinSendSMS.php');
require_once ('config.php');
class SendSMS {
	private $config;
	
	public function __construct(){
		include("config.php");
		$this->config = $config;
	}
	
	public function sendSMSMessage($receiverNumberWCountryCode, $text) {
		
		// Create a new message
		$objMessage = new SMSMessage();
		$objMessage->ReceiverNumber =$receiverNumberWCountryCode;
		$objMessage->SenderNumber = 'STF';
		$objMessage->Text = $text;
		$objMessage->Tariff = 0;
		$objMessage->TimeToLive = 0;
		$objMessage->RequestReceipt = false;
		
		// Create parameters
		$objSendSingleMessage = new SendSingleMessage();
		$objSendSingleMessage->m = $objMessage;
		$objSendSingleMessage->username = $this->config["pswin_username"];
		$objSendSingleMessage->password = $this->config["pswin_password"];
		
		// Connect to service
		$objService = new SMSService();
		
		// Send message
		error_log("sending SMS", 0);
		$objReturn = $objService->SendSingleMessage($objSendSingleMessage);

		var_dump($objReturn);
	}
}


?>
