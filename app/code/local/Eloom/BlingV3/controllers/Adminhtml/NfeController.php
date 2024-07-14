<?php

##eloom.licenca##

class Eloom_BlingV3_Adminhtml_NfeController extends Mage_Adminhtml_Controller_Action {
	
	protected $configModel;
	
	protected $serieNfe;
	private $logger;
	
	protected function _construct() {
		$this->configModel = Mage::getModel('eloom_blingv3/config');
		$this->serieNfe = $this->configModel->getNfeOutSerie();
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		
		parent::_construct();
	}
	
	function sendNfeAction() {
		try {
			$orderIds = $this->getRequest()->getParam('order_ids');
			if (!is_array($orderIds)) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Please select Order(s).'));
			}
			
			foreach ($orderIds as $orderId) {
				try {
					$order = Mage::getModel('sales/order')->load($orderId);
					
					$isEnabled = $this->configModel->isInitialStatusMappedOnNfeOut($order->getStatus());
					if (!$isEnabled) {
						return Eloom_BlingV3_Result::getInstance()->addErrorMessage(sprintf('Pedido %s - Status não liberado para gerar NF.', $order->getIncrementId()));
					}
					$serieNfe = $this->serieNfe;
					if (empty($serieNfe)) {
						return Eloom_BlingV3_Result::getInstance()->addErrorMessage(sprintf('Pedido %s - Série da NF não informada.', $order->getIncrementId()));
					}
					
					$nfe = Mage::getModel('eloom_blingv3/nfe');
					$nfe->create()->setOrderId($order->getId())
						->setStoreId(trim($order->getStoreId()))
						->save();
				} catch (Exception $exc) {
					$this->logger->error(sprintf("Pedido %s - %s", $orderId, $exc->getTraceAsString()));
				}
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
		
		$this->_redirectReferer();
	}
	
	protected function _isAllowed() {
		return true;
	}
	
}
