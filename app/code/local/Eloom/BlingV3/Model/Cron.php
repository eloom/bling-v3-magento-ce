<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Cron extends Mage_Core_Model_Abstract {

	const PAGE_RESULTS = 10;

	private $logger;

	/**
	 * Initialize resource model
	 */
	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	/**
	 * 1 - Busca as as NFs na tabela eloom_blingv3_nfe" cujo campo "access_key" seja "NULL".<br/>
	 *
	 * 2 - Salva a chave de acesso da NF e insere o comentário no pedido.<br />
	 *
	 * @return void
	 */
	public function proccessNfe() {
		$this->logger->info('BlingV3 - Buscando NF - início');
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('access_key', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-1 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');

		if (!$collection->getSize()) {
			return;
		}
		$config = Mage::getModel('eloom_blingv3/config');
		$apikey = $config->getApiKey();
		$serie = $config->getNfeOutSerie();
		$comment = $config->getNfeOutComment();

		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');

		$totalRecords = $collection->getSize();
		$num = ($totalRecords / self::PAGE_RESULTS);
		$offset = 0;
		$pageValue = 1;

		for($i = 0; $i < $num; $i++) {
			if ($pageValue > 1) {
				$offset = ($pageValue - 1) * self::PAGE_RESULTS;
			}

			$collection = $this->getAccessKeyCollection(self::PAGE_RESULTS, $offset);
			if (!$collection->getSize()) {
				break;
			}

			foreach($collection as $nfe) {
				$this->logger->info(sprintf("BlingV3 - Buscando NF %s do Pedido %s", $nfe->getBlingNumber(), $nfe->getOrderId()));
				try {
					$response = $nfeService->getNfe($apikey, $nfe->getBlingNumber(), $serie);
					if ($response && isset($response->retorno->erros[0]->erro->cod) && $response->retorno->erros[0]->erro->cod == 14) {
						$nfe->delete();
						continue;
					}

					if ($response && is_array($response->retorno->notasfiscais)) {
						foreach($response->retorno->notasfiscais as $element) {
							$chaveAcesso = $element->notafiscal->chaveAcesso;
							if ($chaveAcesso) {
								$nfe->setAccessKey($chaveAcesso);
								if (!empty($comment)) {
									$comment = sprintf($comment, $chaveAcesso);
									$nfe->addStatusHistoryComment($comment);
								}
								$nfe->setDanfeUrl($element->notafiscal->linkDanfe);
								$nfe->setStatus($element->notafiscal->situacao);
								$nfe->save();
							}
						}
					}
				} catch(Exception $e) {
					$this->logger->error($e->getMessage());
				} finally {
					$chaveAcesso = $nfe->getNfeAccessKey();
					if (!empty($chaveAcesso)) {
						Mage::dispatchEvent('eloom_marketplace_nfe_save', array('order_id' => $nfe->getOrderId(), 'nfe_number' => $nfe->getNfeNumber(), 'nfe_access_key' => $nfe->getNfeAccessKey()));
					}
				}
			}

			$pageValue++;
		}

		$this->logger->info('BlingV3 - Buscando NF - fim');
	}

	/**
	 * 1 - Busca as as NFs na tabela "eloom_blingv3_nfe" cujo campo "access_key" seja "NULL".<br/>
	 *
	 * 2 - Salva a chave de acesso da NF e insere o comentário no pedido.<br />
	 *
	 * 3 - Informa o rastreamento se ainda não tem.
	 *
	 *
	 * @return void
	 */
	public function proccessTrackings() {
		$config = Mage::getModel('eloom_blingv3/config');
		if ($config->isManuallyTracking()) {
			$this->logger->info('BlingV3 - Sistema configurado para inserir o localizador manualmente.');

			return true;
		}
		$this->logger->info('BlingV3 - Buscando Localizadores - início');
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('tracking_number', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-3 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');
		//$collection->getSelect()->limit(100);

		if (!$collection->getSize()) {
			return;
		}

		$apikey = $config->getApiKey();
		$serie = $config->getNfeOutSerie();
		$nfeService = Mage::getModel('eloom_blingv3/service_nfe');

		$totalRecords = $collection->getSize();
		$num = ($totalRecords / self::PAGE_RESULTS);
		$offset = 0;
		$pageValue = 1;

		for($i = 0; $i < $num; $i++) {
			if ($pageValue > 1) {
				$offset = ($pageValue - 1) * self::PAGE_RESULTS;
			}

			$collection = $this->getTrackingNumberCollection(self::PAGE_RESULTS, $offset);
			if (!$collection->getSize()) {
				break;
			}

			foreach($collection as $nfe) {
				try {
					$this->logger->info(sprintf("BlingV3 - Buscando Localizador - Pedido %s", $nfe->getOrderId()));

					$response = $nfeService->getNfe($apikey, $nfe->getBlingNumber(), $serie);
					if ($response && is_array($response->retorno->notasfiscais)) {
						foreach($response->retorno->notasfiscais as $element) {
							try {
								$trackingNumber = $nfe->getTrackingNumber();
								if (empty($trackingNumber)) {
									$order = Mage::getModel('sales/order')->load($nfe->getOrderId());
									$shippingMethodCode = $config->getShippingMethodCode($order->getShippingMethod());

									$rastreador = $element->notafiscal->codigosRastreamento->codigoRastreamento;
									if ($config->isTrackingNumberIsNfNumber($shippingMethodCode)) {
										$rastreador = $element->notafiscal->numero;
									}
									if (!empty($rastreador) && $config->isSaveTracking($nfe->getCreatedAt())) {
										$nfe->setTrackingNumber(trim($rastreador));
										$nfe->setStatus($element->notafiscal->situacao);

										$nfe->save();

										Mage::getModel('eloom_blingv3/shipment')->createShipment($order, $rastreador, $shippingMethodCode);
									}
								}
							} catch(Exception $e2) {
								$this->logger->error($e2->getMessage());
							}
						}
					}
				} catch(Exception $e) {
					$this->logger->error($e->getMessage());
				}
			}

			$pageValue++;
		}

		$this->logger->info('BlingV3 - Buscando Localizadores - fim');
	}

	/**
	 * Retorno os registros com o "tracking_number" null
	 *
	 * @param $pageResult
	 * @param $offset
	 * @return mixed
	 */
	protected function getTrackingNumberCollection($pageResult, $offset) {
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('tracking_number', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-3 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');
		$collection->getSelect()->limit(intval($pageResult), $offset);

		return $collection;
	}

	/**
	 * Retorno os registros com o "access_key" null
	 *
	 * @param $pageResult
	 * @param $offset
	 * @return mixed
	 */
	protected function getAccessKeyCollection($pageResult, $offset) {
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('access_key', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-1 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');
		$collection->getSelect()->limit(intval($pageResult), $offset);

		return $collection;
	}

}
