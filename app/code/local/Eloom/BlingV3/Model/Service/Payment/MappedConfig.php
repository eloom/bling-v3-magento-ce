<?php

##eloom.licenca##

require_once 'PaymentMethod.php';

class Eloom_BlingV3_Model_Service_Payment_MappedConfig extends Mage_Core_Model_Abstract {

	public function prepare($orderId) {
		$config = Mage::getModel('eloom_blingv3/config');
		$paymentMethods = unserialize($config->getPaymentMapped());

		$order = Mage::getModel('sales/order')->load($orderId);
		$payment = $order->getPayment();
		$paymentInstance = $payment->getMethodInstance();
		$title = $paymentInstance->getTitle();

		if (is_array($paymentMethods)) {
			$labels = array();
			foreach($paymentMethods as $value) {
				$labels[$value['method']] = $value;
				if ($value['method'] == $paymentInstance->getCode()) {
					$title = $value['bling_description'];
					break;
				}
			}
		}

		$createTime = $order->getUpdatedAt();

		$paymentMethod = new PaymentMethod();
		$paymentMethod->setAmount($payment->getBaseAmountPaid());
		$days = 1;
		$paymentMethod->setDays($days);
		$paymentMethod->setMethod($title);
		//$paymentMethod->setObservations(sprintf("%s", $paymentInstance->getInfo()->getCcType()));

		$paymentDay = Mage::getModel('core/date')->date('d/m/Y', strtotime($createTime . " + $days day"));
		$paymentMethod->setPaymentDay($paymentDay);

		return array($paymentMethod);
	}

}
