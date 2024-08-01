<?php

##eloom.licenca##

class Eloom_BlingV3_Adminhtml_NfeController extends Mage_Adminhtml_Controller_Action {

	private $logger;

	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);

		parent::_construct();
	}

	function sendNfeAction() {

		try {
			$orderIds = $this->getRequest()->getParam('order_ids');
			if (!is_array($orderIds)) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Please select Order(s).'));
			}

			$config = Mage::getModel('eloom_blingv3/config');
			$serieNfe = $config->getNfeOutSerie();

			foreach ($orderIds as $orderId) {
				try {
					$order = Mage::getModel('sales/order')->load($orderId);

					$isEnabled = $config->isInitialStatusMappedOnNfeOut($order->getStatus());
					if (!$isEnabled) {
						$this->logger->error(sprintf('Pedido %s - Status não liberado para gerar NF.', $order->getIncrementId()));
						Mage::getSingleton('adminhtml/session')->addError(sprintf('Pedido %s - Status não liberado para gerar NF.', $order->getIncrementId()));
					}
					if (empty($serieNfe)) {
						$this->logger->error(sprintf('Pedido %s - Série da NF não informada.', $order->getIncrementId()));
						Mage::getSingleton('adminhtml/session')->addError(sprintf('Pedido %s - Série da NF não informada.', $order->getIncrementId()));
					}

					$nfeModel = Mage::getModel('eloom_blingv3/nfe');
					$nfe = $nfeModel->load($order->getId(), 'order_id');

					if (null != $nfe && !$nfe->getOrderId()) {
						$nfeModel->create()->setOrderId($order->getId())
							->setStoreId(trim($order->getStoreId()))
							->save();
					}
				} catch (\Exception $e) {
					$this->logger->error(sprintf("Pedido %s - %s", $orderId, $e->getTraceAsString()));
				}
			}

			try {
				$nfeService = Mage::getModel('eloom_blingv3/service_nfe');
				$nfeService->generateNfeOut();
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage());
			}
		} catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}

		$this->_redirectReferer();
	}

	protected function _isAllowed() {
		return true;
	}

}
