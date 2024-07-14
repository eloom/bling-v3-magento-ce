<?php

##eloom.licenca##

class Eloom_BlingV3_Result {

	private $errors;
	private $success;
	public static $instance;

	private function __construct() {
		$this->errors = array();
		$this->success = array();
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new Eloom_BlingV3_Result();
		}
		return self::$instance;
	}

	public function addSuccessMessage($message) {
		$this->success[] = $message;
	}

	public function addErrorMessage($message) {
		$this->errors[] = $message;
	}

	public function getErrorsMessages() {
		return $this->errors;
	}

	public function getSuccessMessages() {
		return $this->success;
	}

	public function reset() {
		$this->errors = null;
		$this->success = null;
	}

}
