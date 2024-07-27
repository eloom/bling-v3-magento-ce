<?php

##eloom.licenca##

use Eloom\SdkBling\Bling;

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
						return Eloom_BlingV3_Result::getInstance()->addErrorMessage(sprintf('Pedido %s - Status não liberado para gerar NF.', $order->getIncrementId()));
					}
					if (empty($serieNfe)) {
						return Eloom_BlingV3_Result::getInstance()->addErrorMessage(sprintf('Pedido %s - Série da NF não informada.', $order->getIncrementId()));
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
				$nfeService->processNfeOut();
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage());
			}

			foreach (Eloom_BlingV3_Result::getInstance()->getSuccessMessages() as $message) {
				Mage::getSingleton('adminhtml/session')->addSuccess($message);
			}
			foreach (Eloom_BlingV3_Result::getInstance()->getErrorsMessages() as $message) {
				Mage::getSingleton('adminhtml/session')->addError($message);
			}
			Eloom_BlingV3_Result::getInstance()->reset();

		} catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}

		/**
		 * Refresh Token
		 */
		//$client = Bling::of($config->getApiKey(), $config->getSecretKey(), null);
		//$retorno = $client->refreshToken($config->getRefreshToken());

		$this->_redirectReferer();
	}

	protected function _isAllowed() {
		return true;
	}

}
