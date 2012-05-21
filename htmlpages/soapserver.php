<?php //server.php
require_once('pswin/ReturnValue.php');

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache

require_once ('smsreceive.php');
require_once ('mmsreceive.php');
class SMSReceiveService {
	/**
	 * This function is called from PSWin when pushing MMS
	 *
	 * @param ReceiveMMSMessage $parameters
	 * @return ReceiveMMSMessageResponse
	 */
	public static function ReceiveMMSMessage(ReceiveMMSMessage $parameters) {
		$res = new ReceiveMMSMessageResponse();
		
		error_log("in ReceiveMMSMessage");
		
		$m = $parameters->getM();
		$sender = $m->getSenderNumber();
		$subject = $m->getSubject();
		$data = $m->getData();
		
		if(empty($sender) || empty($data)) {
			error_log("Recived MMS message, but either sender number og content (data) was empty: sender: "+$sender,0);
			$res->ReceiveMMSMessageResult = new ReturnValue('500', 'No sender or data', 'No sender or data');
			return $res;
		}
		
		// Call function to handle the incoming SMS
		$mmsHandler = new MMSReceiveHandler();
		$mmsHandler->handleMms($sender, $subject, $data);

		$res->ReceiveMMSMessageResult = new ReturnValue('200', $subject, $subject);
		
		return $res;
		
	}
	
	/**
	 * This function is called from PSWin when pushing SMS
	 *
	 * @param ReceiveSMSMessage $parameters
	 * @return ReceiveSMSMessageResponse
	 */
	public static function ReceiveSMSMessage(ReceiveSMSMessage $parameters) {
		$res = new ReceiveSMSMessageResponse();
		
		$m = $parameters->getM();
		$sender = $m->getSenderNumber();
		$message = $m->getText();
		
		if(empty($sender) || empty($message)) {
			error_log("Recived SMS message, but either sender number og text was empty: sender: "+$sender+", message: "+message,0);
			$res->ReceiveSMSMessageResult = new ReturnValue('500', 'No sender or text', 'No sender or text');
			return $res;
		}

		// Call function to handle the incoming SMS
		$smsHandler = new SMSReceiveHandler();
		$smsHandler->handleSms($sender, $message);
		
		// Return
		$res->ReceiveSMSMessageResult = new ReturnValue('200', 'OK', 'OK');
		return $res;
	}


}
$classmap = array(
		'ReceiveSMSMessage' => 'ReceiveSMSMessage',
		'IncomingSMSMessage' => 'IncomingSMSMessage',
		'GSMPosition' => 'GSMPosition',
		'ReceiveSMSMessageResponse' => 'ReceiveSMSMessageResponse',
		'ReturnValue' => 'ReturnValue',
		'ReceiveDeliveryReport' => 'ReceiveDeliveryReport',
		'DeliveryReport' => 'DeliveryReport',
		'ReceiveDeliveryReportResponse' => 'ReceiveDeliveryReportResponse',
		'ReceiveMMSMessage' => 'ReceiveMMSMessage',
		'IncomingMMSMessage' => 'IncomingMMSMessage',
		'ReceiveMMSMessageResponse' => 'ReceiveMMSMessageResponse',
);
$server = new SoapServer("http://sms.pswin.com/SOAP/Receive.asmx?wsdl", array('classmap' => $classmap));
$server->setClass('SMSReceiveService');
//$server->addFunction("ReceiveSMSMessage");
//$server->addFunction("ReceiveMMSMessage");
$server->handle();




class ReceiveSMSMessage {
	/* @var $m IncomingSMSMessage */
	public $m; 
	
	/**
	 *
	 * @return IncomingSMSMessage
	 */
	public function getM () {
		return $this->m;
	}
}

class IncomingSMSMessage {
	public $ReceiverNumber; // string
	public $SenderNumber; // string
	public $Text; // string
	public $Network; // string
	public $Address; // string
	public $Position; // GSMPosition
	
	public function getReceiverNumber(){
		return $this->ReceiverNumber;
	}
	public function getSenderNumber(){
		return $this->SenderNumber;
	}
	public function getText(){
		return $this->Text;
	}

}

class GSMPosition {
	public $Longitude; // string
	public $Lattitude; // string
	public $Radius; // string
	public $County; // string
	public $Council; // string
	public $CouncilNumber; // string
	public $Place; // string
	public $SubPlace; // string
	public $ZipCode; // string
	public $City; // string
}

class ReceiveSMSMessageResponse {
	public $ReceiveSMSMessageResult; // ReturnValue
}

class ReceiveMMSMessage {
	public $m; // IncomingMMSMessage
	
	/**
	 *
	 * @return IncomingMMSMessage
	 */
	public function getM () {
		return $this->m;
	}
}

class IncomingMMSMessage {
	public $ReceiverNumber; // string
	public $SenderNumber; // string
	public $Subject; // string
	public $Network; // string
	public $Address; // string
	public $Position; // GSMPosition
	public $Data; // base64Binary
	
	public function getReceiverNumber(){
		return $this->ReceiverNumber;
	}
	public function getSenderNumber(){
		return $this->SenderNumber;
	}
	public function getText(){
		return $this->Text;
	}
	public function getSubject(){
		return $this->Subject;
	}
	public function getData(){
		return $this->Data;
	}
}

class ReceiveMMSMessageResponse {
	public $ReceiveMMSMessageResult; // ReturnValue
}

