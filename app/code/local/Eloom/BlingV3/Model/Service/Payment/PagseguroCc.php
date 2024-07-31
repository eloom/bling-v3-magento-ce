<?php

##eloom.licenca##

require_once 'PaymentMethod.php';

class Eloom_BlingV3_Model_Service_Payment_PagseguroCc extends Mage_Core_Model_Abstract {

	public function prepare($orderId) {
		$order = Mage::getModel('sales/order')->load($orderId);
		$payment = $order->getPayment();

		$paymentInstance = $payment->getMethodInstance();
		$additionalData = json_decode($payment->getAdditionalData());
		$installments = array();

		$totalInstallments = 1;
		if (!empty($additionalData)) {
			if (isset($additionalData->installments)) {
				$totalInstallments = $additionalData->installments;
			}
		}
		$createTime = $order->getUpdatedAt();

		for($i = 0; $i < $totalInstallments; $i++) {
			$paymentMethod = new PaymentMethod();
			$paymentMethod->setAmount($additionalData->installmentAmount);
			$days = $i + 1;
			$days = $days * 30;
			$paymentMethod->setDays($days);
			$paymentMethod->setMethod($paymentInstance->getTitle());
			$paymentMethod->setObservations(sprintf("%s", $payment->getCcType()));

			$paymentDay = Mage::getModel('core/date')->date('y-m-d', strtotime($createTime . " + $days day"));
			$paymentMethod->setPaymentDay($paymentDay);

			$installments[] = $paymentMethod;
		}

		return $installments;
	}

}
