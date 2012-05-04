<?php
class ReturnValue {
	public $Code; // int
	public $Description; // string
	public $Reference; // string
	
	public function __construct($code, $description, $ref) {
		$this->Code = $code;
		$this->Description = $description;
		$this->Reference = $ref;
	}
}