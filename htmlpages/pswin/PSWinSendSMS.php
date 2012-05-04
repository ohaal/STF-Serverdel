<?php
require_once('ReturnValue.php');

class SendSingleMessage {
  public $username; // string
  public $password; // string
  public $m; // SMSMessage
}

class SMSMessage {
  public $ReceiverNumber; // string
  public $SenderNumber; // string
  public $Text; // string
  public $Network; // string
  public $TypeOfMessage; // string
  public $Tariff; // int
  public $TimeToLive; // int
  public $CPATag; // string
  public $RequestReceipt; // boolean
  public $SessionData; // string
  public $AffiliateProgram; // string
  public $DeliveryTime; // string
  public $ServiceCode; // string
}

class SendSingleMessageResponse {
	/* @var $SendSingleMessageResult */
	public $SendSingleMessageResult; // ReturnValue

  /**
   *
   * @return ReturnValue
   */
  public function getSendSingleMessageResult () {
  	return $this->SendSingleMessageResult;
  }
}

class SendMessages {
  public $username; // string
  public $password; // string
  public $m; // ArrayOfSMSMessage
}

class SendMessagesResponse {
  public $SendMessagesResult; // ArrayOfReturnValue
}

class SendSingleWapPush {
  public $username; // string
  public $password; // string
  public $m; // WapPushMessage
}

class WapPushMessage {
  public $ReceiverNumber; // string
  public $SenderNumber; // string
  public $Url; // string
  public $Description; // string
  public $Network; // string
  public $Tariff; // int
  public $TimeToLive; // int
  public $CPATag; // string
  public $RequestReceipt; // boolean
  public $SessionData; // string
  public $AffiliateProgram; // string
  public $DeliveryTime; // string
}

class SendSingleWapPushResponse {
  public $SendSingleWapPushResult; // ReturnValue
}

class SendMultipleWapPush {
  public $username; // string
  public $password; // string
  public $m; // ArrayOfWapPushMessage
}

class SendMultipleWapPushResponse {
  public $SendMultipleWapPushResult; // ArrayOfReturnValue
}


/**
 * SMSService class
 * 
 * PSWinCom SMS Gateway SOAP Interface 
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class SMSService extends SoapClient {

  private static $classmap = array(
                                    'SendSingleMessage' => 'SendSingleMessage',
                                    'SMSMessage' => 'SMSMessage',
                                    'SendSingleMessageResponse' => 'SendSingleMessageResponse',
                                    'ReturnValue' => 'ReturnValue',
                                    'SendMessages' => 'SendMessages',
                                    'SendMessagesResponse' => 'SendMessagesResponse',
                                    'SendSingleWapPush' => 'SendSingleWapPush',
                                    'WapPushMessage' => 'WapPushMessage',
                                    'SendSingleWapPushResponse' => 'SendSingleWapPushResponse',
                                    'SendMultipleWapPush' => 'SendMultipleWapPush',
                                    'SendMultipleWapPushResponse' => 'SendMultipleWapPushResponse',
                                   );

  public function SMSService($wsdl = "http://sms.pswin.com/SOAP/SMS.asmx?wsdl", $options = array()) {
    foreach(self::$classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }
    parent::__construct($wsdl, $options);
  }

  /**
   *  
   *
   * @param SendSingleMessage $parameters
   * @return SendSingleMessageResponse
   */
  public function SendSingleMessage(SendSingleMessage $parameters) {
    return $this->__soapCall('SendSingleMessage', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/SMS',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SendMessages $parameters
   * @return SendMessagesResponse
   */
  public function SendMessages(SendMessages $parameters) {
    return $this->__soapCall('SendMessages', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/SMS',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SendSingleWapPush $parameters
   * @return SendSingleWapPushResponse
   */
  public function SendSingleWapPush(SendSingleWapPush $parameters) {
    return $this->__soapCall('SendSingleWapPush', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/SMS',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SendMultipleWapPush $parameters
   * @return SendMultipleWapPushResponse
   */
  public function SendMultipleWapPush(SendMultipleWapPush $parameters) {
    return $this->__soapCall('SendMultipleWapPush', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/SMS',
            'soapaction' => ''
           )
      );
  }

}

?>
