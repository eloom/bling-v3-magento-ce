<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Shipment extends Mage_Core_Model_Abstract {

	private $logger;

	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	/**
	 *
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param type $trackingNumber
	 * @param type $shippingMethodCode
	 */
	public function createShipment(Mage_Sales_Model_Order $order, $trackingNumber, $shippingMethodCode) {
		if (!$order->hasShipments()) {
			$this->logger->info(sprintf("BlingV3 - Criando entrega %s para o pedido %s, com %s", $trackingNumber, $order->getId(), $shippingMethodCode));
			$itemQtys = array();
			foreach($order->getAllItems() as $orderItem) {
				if ($orderItem->getQtyToShip() && !$orderItem->getIsVirtual()) {
					$itemQtys[$orderItem->getId()] = $orderItem->getQtyToShip();
				}
			}
			$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQtys);
			$shipment->register();
			$order->setIsInProcess(true);
			Mage::getModel('core/resource_transaction')->addObject($shipment)->addObject($order)->save();

			$order = Mage::getModel('sales/order')->load($order->getId());
		}
		/**
		 * Verficando se há tracking
		 */
		$shipment = $order->getShipmentsCollection()->getFirstItem();
		$isTracking = count($shipment->getAllTracks());

		if ($isTracking == 0 && $trackingNumber) {
			$shippingMethodName = Mage::getStoreConfig("carriers/$shippingMethodCode/title");
			$track = Mage::getModel('sales/order_shipment_track');
			$track->setShipment($shipment)->setData('title', $shippingMethodName)->setData('number', $trackingNumber)->setData('carrier_code', $shippingMethodCode)->setData('order_id', $order->getId())->save();
			$shipment->addTrack($track);
			$this->logger->info(sprintf("BlingV3 - Inserindo %s no pedido %s.", $trackingNumber, $order->getId()));

			try {
				switch($shippingMethodCode) {
					case 'eloom_correios':
						if (Mage::helper('eloom_platform/module')->isCorreiosSroExists()) {
							Mage::getModel('eloom_correiossro/sonda')->create()->setNumber(trim($track->getNumber()))->setOrderId(trim($track->getOrderId()))->setStoreId(trim($track->getStoreId()))->save();
							$this->logger->info(sprintf("Inserindo %s na sonda dos Correios SRO.", $trackingNumber));
						}
						break;

					case 'eloom_jadlog':
						break;
				}
			} catch(Exception $exc) {
				$this->logger->error($exc->getTraceAsString());
			}

			/**
			 * Enviando email
			 */
			$shipment->setEmailSent(true);
			$shipment->sendEmail(true, '');
			$shipment->save();
		}
	}

}
