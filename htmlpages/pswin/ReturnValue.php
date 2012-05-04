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
	
	public function getCode(){
		return $this->Code;
	}
	public function getDescription(){
		return $this->Description;
	}
	public function getReference(){
		return $this->Reference;
	}
	
}