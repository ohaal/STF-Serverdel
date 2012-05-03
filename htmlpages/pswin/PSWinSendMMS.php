<?php
class SendSingleMMSMessage {
  public $username; // string
  public $password; // string
  public $m; // MMSMessage
}

class MMSMessage {
  public $ReceiverNumber; // string
  public $SenderNumber; // string
  public $Subject; // string
  public $Network; // string
  public $Tariff; // int
  public $TimeToLive; // int
  public $CPATag; // string
  public $RequestReceipt; // boolean
  public $SessionData; // string
  public $AffiliateProgram; // string
  public $Data; // base64Binary
}

class SendSingleMMSMessageResponse {
  public $SendSingleMMSMessageResult; // ReturnValue
}

class ReturnValue {
  public $Code; // int
  public $Description; // string
  public $Reference; // string
}

class SendMMSMessages {
  public $username; // string
  public $password; // string
  public $m; // ArrayOfMMSMessage
}

class SendMMSMessagesResponse {
  public $SendMMSMessagesResult; // ArrayOfReturnValue
}


/**
 * MMSService class
 * 
 * PSWinCom MMS Gateway SOAP Interface 
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class MMSService extends SoapClient {

  private static $classmap = array(
                                    'SendSingleMMSMessage' => 'SendSingleMMSMessage',
                                    'MMSMessage' => 'MMSMessage',
                                    'SendSingleMMSMessageResponse' => 'SendSingleMMSMessageResponse',
                                    'ReturnValue' => 'ReturnValue',
                                    'SendMMSMessages' => 'SendMMSMessages',
                                    'SendMMSMessagesResponse' => 'SendMMSMessagesResponse',
                                   );

  public function MMSService($wsdl = "http://sms.pswin.com/SOAP/MMS.asmx?wsdl", $options = array()) {
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
   * @param SendSingleMMSMessage $parameters
   * @return SendSingleMMSMessageResponse
   */
  public function SendSingleMMSMessage(SendSingleMMSMessage $parameters) {
    return $this->__soapCall('SendSingleMMSMessage', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/MMS',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SendMMSMessages $parameters
   * @return SendMMSMessagesResponse
   */
  public function SendMMSMessages(SendMMSMessages $parameters) {
    return $this->__soapCall('SendMMSMessages', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Submit/MMS',
            'soapaction' => ''
           )
      );
  }

}

?>
