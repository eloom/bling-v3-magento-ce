<?php

##eloom.licenca##

class PaymentMethod {

	private $days;
	private $observations;
	private $paymentDay;
	private $amount;
	private $method;

	public function getDays() {
		return $this->days;
	}

	public function getObservations() {
		return $this->observations;
	}

	public function getPaymentDay() {
		return $this->paymentDay;
	}

	public function getAmount() {
		return $this->amount;
	}

	function setDays($days) {
		$this->days = $days;
	}

	function setObservations($observations) {
		$this->observations = $observations;
	}

	function setPaymentDay($paymentDay) {
		$this->paymentDay = $paymentDay;
	}

	function setAmount($amount) {
		$this->amount = $amount;
	}

	public function getMethod() {
		return $this->method;
	}

	function setMethod($method) {
		$this->method = $method;
	}
}
