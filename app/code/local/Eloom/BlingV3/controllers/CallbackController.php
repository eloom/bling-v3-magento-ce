<?php

##eloom.licenca##

use Eloom\SdkBling\Bling;

class Eloom_BlingV3_CallbackController extends Mage_Core_Controller_Front_Action {

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

		$config = Mage::getModel('eloom_blingv3/config');
		//$client = Bling::of($config->getApiKey(), $config->getSecretKey());
		//$retorno = $client->requestToken($code);
		//$this->logger->info($retorno);
	}
}
