// <?php
class ReceiveSMSMessage {
  public $m; // IncomingSMSMessage
}

class IncomingSMSMessage {
  public $ReceiverNumber; // string
  public $SenderNumber; // string
  public $Text; // string
  public $Network; // string
  public $Address; // string
  public $Position; // GSMPosition
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

class ReturnValue {
  public $Code; // int
  public $Description; // string
  public $Reference; // string
}

class ReceiveDeliveryReport {
  public $dr; // DeliveryReport
}

class DeliveryReport {
  public $State; // string
  public $ReceiverNumber; // string
  public $DeliveryTime; // string
  public $Reference; // string
}

class ReceiveDeliveryReportResponse {
  public $ReceiveDeliveryReportResult; // ReturnValue
}

class ReceiveMMSMessage {
  public $m; // IncomingMMSMessage
}

class IncomingMMSMessage {
  public $ReceiverNumber; // string
  public $SenderNumber; // string
  public $Subject; // string
  public $Network; // string
  public $Address; // string
  public $Position; // GSMPosition
  public $Data; // base64Binary
}

class ReceiveMMSMessageResponse {
  public $ReceiveMMSMessageResult; // ReturnValue
}


/**
 * SMSReceive class
 * 
 *  
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class SMSReceive extends SoapClient {

  private static $classmap = array(
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

  public function SMSReceive($wsdl = "http://sms.pswin.com/SOAP/Receive.asmx?wsdl", $options = array()) {
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
   * @param ReceiveSMSMessage $parameters
   * @return ReceiveSMSMessageResponse
   */
  public function ReceiveSMSMessage(ReceiveSMSMessage $parameters) {
    return $this->__soapCall('ReceiveSMSMessage', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Receive',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param ReceiveDeliveryReport $parameters
   * @return ReceiveDeliveryReportResponse
   */
  public function ReceiveDeliveryReport(ReceiveDeliveryReport $parameters) {
    return $this->__soapCall('ReceiveDeliveryReport', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Receive',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param ReceiveMMSMessage $parameters
   * @return ReceiveMMSMessageResponse
   */
  public function ReceiveMMSMessage(ReceiveMMSMessage $parameters) {
    return $this->__soapCall('ReceiveMMSMessage', array($parameters),       array(
            'uri' => 'http://pswin.com/SOAP/Receive',
            'soapaction' => ''
           )
      );
  }

}

?>
