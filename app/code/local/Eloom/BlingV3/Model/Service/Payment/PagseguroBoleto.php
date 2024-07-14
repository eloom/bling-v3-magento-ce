<?php

##eloom.licenca##

require_once 'PaymentMethod.php';

class Eloom_BlingV3_Model_Service_Payment_PagseguroBoleto extends Mage_Core_Model_Abstract {

	public function prepare($orderId) {
		$order = Mage::getModel('sales/order')->load($orderId);
		$payment = $order->getPayment();

		$paymentInstance = $payment->getMethodInstance();
		$createTime = $order->getUpdatedAt();

		$paymentMethod = new PaymentMethod();
		$paymentMethod->setAmount($payment->getBaseAmountPaid());
		$days = 1;
		$days = $days + Eloom_BlingV3_Model_Service_Payment_Proxy::BOLETO_DAYS;
		$paymentMethod->setDays($days);
		$paymentMethod->setMethod($paymentInstance->getTitle());
		//$paymentMethod->setObservations(sprintf("%s", $paymentInstance->getInfo()->getCcType()));

		$paymentDay = Mage::getModel('core/date')->date('d/m/Y', strtotime($createTime . " + $days day"));
		$paymentMethod->setPaymentDay($paymentDay);

		return array($paymentMethod);
	}

}
