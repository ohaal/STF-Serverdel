<?php

include_once('PSWinSendSMS.php');

class SendSMS {
		
	public function SendSMSMessage($receiverNumberWCountryCode, $text) {
			
		// Create a new message
		$objMessage = new SMSMessage();
		$objMessage->ReceiverNumber =$receiverNumberWCountryCode;
		$objMessage->SenderNumber = 'PSWinCom';
		$objMessage->Text = $text;
		$objMessage->Tariff = 0;
		$objMessage->TimeToLive = 0;
		$objMessage->RequestReceipt = false;
		
		// Create parameters
		$objSendSingleMessage = new SendSingleMessage();
		$objSendSingleMessage->m = $objMessage;
		$objSendSingleMessage->username = '(username)';
		$objSendSingleMessage->password = '(password)';
		
		// Connect to service
		$objService = new SMSService();
		
		// Send message
		$objReturn = $objService->SendSingleMessage($objSendSingleMessage);
		
		echo '<pre>';
		var_dump($objReturn);
		echo '</pre>';
	
	}
	
}


?>
