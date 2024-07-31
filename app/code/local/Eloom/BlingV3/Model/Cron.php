<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Cron extends Mage_Core_Model_Abstract {

	private $logger;

	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	public function completeNfe() {
		$this->logger->info('BlingV3 - Buscando NF - Início');

		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');
		$nfeService->completeNfe();

		$this->logger->info('BlingV3 - Buscando NF - Fim');
	}

	public function completeTrackings() {
		$this->logger->info('Bling V3 - Buscando Localizadores - Inicio');

		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');
		$nfeService->completeTrackings();

		$this->logger->info('Bling V3 - Buscando Localizadores - Fim');
	}
}
