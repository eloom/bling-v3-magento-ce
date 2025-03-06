<?php

##eloom.licenca##

use Eloom\SdkBling\Bling;

class Eloom_BlingV3_Model_Cron extends Mage_Core_Model_Abstract {

	private $logger;

	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	public function refreshToken() {
		$this->logger->info('BlingV3 - Atualizando token - Início');

		$config = Mage::getModel('eloom_blingv3/config');

		$bling = Bling::of($config->getApiKey(), $config->getSecretKey(), null);
		$response = $bling->refreshToken($config->getRefreshToken());
		$config->saveAccessToken($response->access_token);
		$config->saveRefreshToken($response->refresh_token);

		$this->logger->info('BlingV3 - Token atualizado - Fim');
	}

	public function completeNfe() {
		$this->logger->info('BlingV3 - Buscando NFe - Início');

		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');
		$nfeService->completeNfe();

		$this->logger->info('BlingV3 - Buscando NFe - Fim');
	}

	public function completeTrackings() {
		$this->logger->info('Bling V3 - Buscando Localizadores - Inicio');

		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');
		$nfeService->completeTrackings();

		$this->logger->info('Bling V3 - Buscando Localizadores - Fim');
	}
}
