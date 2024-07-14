<?php

##eloom.licenca##

class Eloom_BlingV3_CallbackController extends Mage_Core_Controller_Front_Action {
	
	const ERROR = 'error';
	const NOTICE = 'notice';
	const SUCCESS = 'success';
	
	private $logger;
	
	/**
	 * Initialize resource model
	 */
	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}
	
	public function indexAction() {
		if (!$this->getRequest()->isGet()) {
			return;
		}
		
		$code = $this->getRequest()->getParam('code');
		$state = $this->getRequest()->getParam('state');
		
		$this->logger->info(sprintf("Token recebido. Código [%s] - Estado [%s].", $code, $state));
		Mage::dispatchEvent('process_callback_event', array('code' => $code));
	}
}
