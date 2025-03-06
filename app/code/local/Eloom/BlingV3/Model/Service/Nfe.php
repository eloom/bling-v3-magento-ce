<?php

##eloom.licenca##

use Eloom\SdkBling\Bling;
use Eloom\SdkBling\Enum\Contribuinte;
use Eloom\SdkBling\Enum\TipoFrete;
use Eloom\SdkBling\Enum\TipoNota;
use Eloom\SdkBling\Enum\TipoPessoa;
use Eloom\SdkBling\Model\Request\FormaPagamento;
use Eloom\SdkBling\Model\Request\Item;
use Eloom\SdkBling\Model\Request\Nfe;
use Eloom\SdkBling\Model\Request\NotasFiscaisTransporteVolume;
use Eloom\SdkBling\Model\Request\Parcela;

class Eloom_BlingV3_Model_Service_Nfe extends Mage_Core_Model_Abstract {

	const PAGE_RESULTS = 10;
	const ATTR_OPERATION_UNIT = 'nfe_operation_unit';
	const ATTR_PRODUCT_SOURCE = 'nfe_product_source';
	const ATTR_NCM = 'nfe_ncm';
	const ATTR_CEST = 'nfe_cest';
	const ATTR_GTIN = 'nfe_gtin';

	private $logger;

	/**
	 * Initialize resource model
	 */
	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	public function generateNfeOut() {
		$this->logger->info("Gerando Nf-e.");

		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('bling_id', array('null' => true));
		$collection->setOrder('entity_id', 'DESC');
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-1 day', time()), 'to' => time(), 'datetime' => true));
		//$collection->getSelect()->limit(100);

		if (!$collection->getSize()) {
			$this->logger->info("Não há Nfe para enviar ao Bling.");
			return;
		}

		$config = Mage::getModel('eloom_blingv3/config');

		$bling = Bling::of(null, null, $config->getAccessToken());

		$nt = $bling->naturezaOperacoes();
		$filtros = [
			'situacao' => 1,
			'descricao' => 'Venda de mercadorias'
		];

		$data = $nt->findAll(0, 100, $filtros);
		//$this->logger->info($data);

		$helper = Mage::helper('eloombootstrap');

		foreach ($collection as $record) {
			try {
				$order = Mage::getModel('sales/order')->load($record->getOrderId());
				$this->logger->info(sprintf("Bling - Buscando NFe do pedido [%s]", $record->getOrderId()));

				//$dataOperacao = Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime(time()));
				$nfe = Nfe::build();
				$nfe->setTipo(TipoNota::SAIDA);
				//$nfe->setNumero(6541);
				//$nfe->setDataEmissao(new DateTime());
				$nfe->setDataOperacao(new DateTime());
				$nfe->setDesconto($this->getExtraAmountValues($order));
				$nfe->setDespesas($this->getInterestAmount($order));

				/**
				 * Contato
				 */
				$address = $this->getAddress($order);

				$contato = $nfe->getContato();
				$contato->setContribuinte(Contribuinte::NAO_CONTRIBUINTE);
				if (null != $order->getCustomerId()) {
					$contato->setId($order->getCustomerId());
				}
				$contato->setNome($order->getCustomerName());

				/**
				 * CPF/CNPJ
				 */
				$taxvat = $order->getCustomerTaxvat();
				$nd = $helper->getOnlyNumbers($taxvat);
				$contato->setNumeroDocumento($nd);
				$contato->setTipoPessoa(TipoPessoa::FISICA);
				if ($order->getCustomerTipoPessoa() != null && $order->getCustomerTipoPessoa() == TipoPessoa::JURIDICA) {
					$contato->setTipoPessoa(TipoPessoa::JURIDICA);
				}

				$phone = preg_replace('/\D/', '', $address->getTelephone());
				$contato->setTelefone($phone);
				$contato->setEmail($order->getCustomerEmail());

				$endereco = $contato->getEndereco();
				$endereco->setEndereco($address->getStreet(1));
				$endereco->setNumero($address->getStreet(2));
				if (!$helper->isEmpty($address->getStreet(3))) {
					$endereco->setComplemento($address->getStreet(3));
				}
				if (!$helper->isEmpty($address->getStreet(4))) {
					$endereco->setBairro($address->getStreet(4));
				}
				$endereco->setCep($address->getPostcode());
				$endereco->setMunicipio($address->getCity());
				$endereco->setUf($address->getRegionCode());
				$endereco->setPais($address->getCountryId());
				$contato->setEndereco($endereco);

				$nfe->setContato($contato);

				/**
				 * Items
				 */
				$productId = $config->getProductId();
				foreach ($order->getAllVisibleItems() as $orderItem) {
					$_product = Mage::getModel('catalog/product')->load($orderItem->product_id);

					$qtd = $orderItem->getQtyOrdered();
					$basePrice = round($orderItem->getBasePrice(), 2);
					if (!empty($qtd) && $basePrice > 0) {
						$name = $orderItem->getName();
						if ($orderItem->getProductType() == 'configurable') {
							$options = unserialize($orderItem->getData('product_options'));
							$name .= '-';

							foreach ($options['attributes_info'] as $attr) {
								$name .= $attr['label'] . ' ' . $attr['value'] . ',';
							}
							$name = trim($name, ',');
						}
						$s = explode('-', $_product->getAttributeText(self::ATTR_PRODUCT_SOURCE));
						$source = preg_replace('/\D/', '', $s[0]);

						$item = Item::of();
						$item->setCodigo($orderItem->getData($productId));
						$item->setDescricao(substr($name, 0, 255));
						$item->setUnidade($_product->getAttributeText(self::ATTR_OPERATION_UNIT));
						/**
						 * CEST
						 */
						$cest = $_product->getData(self::ATTR_CEST);
						if (!empty($cest)) {
							$item->setCest($cest);
						}
						$item->setClassificacaoFiscal($_product->getData(self::ATTR_NCM));
						$item->setOrigem($source);
						//$item->setGtin($_product->getData(self::ATTR_GTIN));

						$item->setQuantidade($qtd);
						$item->setValor($basePrice);
						$item->setTipo('P');

						$nfe->getItens()->push($item);
					}
				}

				/**
				 * Gift Wrap AH
				 */
				if ($order->getBaseAwGiftwrapAmount()) {
					$item = Item::of();
					$item->setCodigo('GIFTWRAP');
					$item->setDescricao('Embalagem especial');
					$item->setUnidade('UN');
					$item->setClassificacaoFiscal('48195000');
					$item->setOrigem('0');
					$item->setQuantidade(1);
					$item->setValor($order->getAwGiftwrapAmount());
					$item->setTipo('P');

					$nfe->getItens()->push($item);
				}

				/**
				 * Transporte
				 */
				$shippingMethod = $order->getShippingMethod();
				$shippingMethods = unserialize($config->getShippingMapped());
				$labels = null;

				$labels = [];
				if (is_array($shippingMethods)) {
					foreach ($shippingMethods as $value) {
						$labels[$value['method']] = $value;
						if ($value['method'] == $shippingMethod) {
							break;
						}
					}
				}

				$transportadora = null;
				$servico = null;
				if (count($labels) > 0) {
					if (array_key_exists($shippingMethod, $labels)) {
						$transportadora = $labels[$shippingMethod]['bling_carrier'];
						$servico = $labels[$shippingMethod]['bling_service'];
					}
				}

				$transporte = $nfe->getTransporte();
				//$transporte->setTipoFrete(TipoFrete::TRANSPORTE_LOGISTICA_CADASTRADA);
				$transporte->setFretePorConta(0);
				$transporte->setFrete(round($order->getBaseShippingAmount(), 2));

				$transportador = $transporte->getTransportador();
				$transportador->setNome($transportadora);
				$transporte->setTransportador($transportador);

				if ($servico) {
					$etiqueta = $transporte->getEtiqueta();
					$etiqueta->setNome($address->getName());

					if (!$helper->isEmpty($address->getStreet(1))) {
						$etiqueta->setEndereco($address->getStreet(1));
					}
					if (!$helper->isEmpty($address->getStreet(2))) {
						$etiqueta->setNumero($address->getStreet(2));
					}
					if (!$helper->isEmpty($address->getStreet(3))) {
						$etiqueta->setComplemento($address->getStreet(3));
					}
					if (!$helper->isEmpty($address->getStreet(4))) {
						$etiqueta->setBairro($address->getStreet(4));
					}
					if (!$helper->isEmpty($address->getCity())) {
						$etiqueta->setMunicipio($address->getCity());
					}
					if (!$helper->isEmpty($address->getRegion())) {
						$etiqueta->setUf($address->getRegionCode());
					}
					if (!$helper->isEmpty($address->getPostcode())) {
						$etiqueta->setCep($address->getPostcode());
					}
					$transporte->setEtiqueta($etiqueta);
				}
				/**
				 * Volumes
				 */
				$vftv = NotasFiscaisTransporteVolume::of();
				$vftv->setServico($servico);
				$transporte->getVolumes()->push($vftv);

				$nfe->setTransporte($transporte);

				/**
				 * Parcelas
				 */
				$installments = Mage::getModel('eloom_blingv3/service_payment_proxy')->parseXml($order->getId(), $order->getPayment());
				if ($installments) {
					foreach ($installments as $paymentMethod) {
						$parcela = Parcela::of();
						if ($paymentMethod->getPaymentDay()) {
							$paymentDay = Mage::getModel('core/date')->date('Y-m-d', strtotime($paymentMethod->getPaymentDay()));
							$parcela->setData(new \DateTime($paymentDay));
						}
						if ($paymentMethod->getAmount()) {
							$parcela->setValor($paymentMethod->getAmount());
						}
						if ($paymentMethod->getMethod()) {
							$formasPagamento = $bling->paymentMethods()->findAll(0, 20, ['descricao' => $paymentMethod->getMethod()])->toArray();
							//$this->logger->info($formasPagamento);
							if (is_array($formasPagamento) && count($formasPagamento) == 1) {
								$forma = FormaPagamento::of();
								$forma->setId($formasPagamento[0]->id);

								$parcela->setFormaPagamento($forma);
							} else {
								throw new \Exception(sprintf("Forma de pagamento [%s] não encontrada/mapeada.", $paymentMethod->getMethod()));
							}
						}
						if ($paymentMethod->getObservations()) {
							$parcela->setObservacoes($paymentMethod->getObservations());
						}

						$nfe->getParcelas()->push($parcela);
					}
				}

				$this->logger->info(json_encode($nfe->jsonSerialize()));

				$response = $bling->nfe()->create($nfe->jsonSerialize());
				//$this->logger->info($response);
				$record->setBlingId(trim($response->id))->setBlingNumber(trim($response->numero))->save();

				/**
				 * Muda Status do Pedido
				 */
				$toStatus = $config->getFinalStatusMappedOnNfeOut($order->getStatus());
				if ($toStatus) {
					$comment = "Nota Fiscal Eletrônica emitida.";
					$order->addStatusHistoryComment($comment, $toStatus, true);
					$order->setStatus($toStatus);
					$order->setIsVisibleOnFront(false);
					$order->save();
					$order->sendOrderUpdateEmail(true, $comment);
				}
				//$message = sprintf('Pedido %s - Gerou NFe %s.', $order->getIncrementId(), trim($response->numero));
				//Eloom_Bling_Result::getInstance()->addSuccessMessage($message);
			} catch (\Exception $e) {
				$this->logger->error(sprintf("Erro ao gerar Nfe, pedido [%s].", $record->getOrderId()));
				$this->logger->error($e->getMessage());

				$error = sprintf('Pedido [%s] - %s', $order->getIncrementId(), $e->getMessage());
				Eloom_Bling_Result::getInstance()->addErrorMessage($error);
			}
		}
	}

	protected function getAddress($order) {
		$address = null;
		if ($order->getIsVirtual()) {
			$address = $order->getBillingAddress();
		} else {
			$address = $order->getShippingAddress();
		}

		return $address;
	}

	/**
	 * Gets extra amount values for order
	 *
	 * @return float
	 */
	protected function getExtraAmountValues($order) {
		$addition = 0.00;
		$discount = 0.00;
		if ($order->getBaseDiscountAmount()) {
			$discount += $order->getBaseDiscountAmount();
		}
		if ($order->getMercadopagoBaseDiscountAmount()) {
			$discount += $order->getMercadopagoBaseDiscountAmount();
		}
		if ($order->getMercadopagoBaseCampaignAmount()) {
			$discount += $order->getMercadopagoBaseCampaignAmount();
		}
		if ($order->getPayuBaseDiscountAmount()) {
			$discount += $order->getPayuBaseDiscountAmount();
		}
		if ($order->getPagseguroBaseDiscountAmount()) {
			$discount += $order->getPagseguroBaseDiscountAmount();
		}
		if ($order->getBaseAffiliateplusDiscount()) {
			$discount += $order->getBaseAffiliateplusDiscount();
		}
		if ($order->getBaseTaxAmount()) {
			$addition += $order->getBaseTaxAmount();
		}
		$amount = $addition + $discount;

		return abs(round($amount, 2));
	}

	protected function getInterestAmount($order) {
		$interest = 0.00;
		if ($order->getMercadopagoBaseInterestAmount()) {
			$interest += $order->getMercadopagoBaseInterestAmount();
		}
		if ($order->getPagseguroBaseInterestAmount()) {
			$interest += $order->getPagseguroBaseInterestAmount();
		}
		if ($order->getPayuBaseInterestAmount()) {
			$interest += $order->getPayuBaseInterestAmount();
		}

		/**
		 * Outros Métodos de Pagamento
		 */
		$payment = $order->getPayment()->getMethodInstance();
		if ('pagarme_creditcard' == $payment->getCode()) {
			$transaction = Mage::getModel('pagarme_core/transaction')->load($order->getId(), 'order_id');
			if ($transaction->payment_method == 'credit_card') {
				if (null != $transaction->rate_amount && $transaction->rate_amount > 0) {
					$interest += $transaction->rate_amount;
				}
			}
		}

		return abs(round($interest, 2));
	}

	public function completeTrackings() {
		$config = Mage::getModel('eloom_blingv3/config');
		if ($config->isManuallyTracking()) {
			$this->logger->info('Bling V3 - Sistema configurado para inserir o localizador manualmente.');

			return true;
		}
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('tracking_number', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-3 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');
		//$collection->getSelect()->limit(100);

		if (!$collection->getSize()) {
			return;
		}

		$totalRecords = $collection->getSize();
		$num = ($totalRecords / self::PAGE_RESULTS);
		$offset = 0;
		$pageValue = 1;

		$bling = Bling::of(null, null, $config->getAccessToken());

		for ($i = 0; $i < $num; $i++) {
			if ($pageValue > 1) {
				$offset = ($pageValue - 1) * self::PAGE_RESULTS;
			}

			$collection = $this->getTrackingNumberCollection(self::PAGE_RESULTS, $offset);
			if (!$collection->getSize()) {
				break;
			}

			foreach ($collection as $record) {
				try {
					$this->logger->info(sprintf("Bling V3 - Buscando Localizador - Pedido %s", $record->getOrderId()));

					$response = $bling->nfe()->find($record->getBlingId());
					//$this->logger->info($response);

					if ($response) {
						try {
							$trackingNumber = $record->getTrackingNumber();
							if (empty($trackingNumber)) {
								$order = Mage::getModel('sales/order')->load($record->getOrderId());
								$shippingMethodCode = $config->getShippingMethodCode($order->getShippingMethod());

								$rastreador = null;
								if (isset($response->transporte->volumes[0]) && isset($response->transporte->volumes[0]->id)) {
									$rastreador = $response->transporte->volumes[0]->id;
									if ($config->isTrackingNumberIsNfNumber($shippingMethodCode)) {
										$rastreador = $response->numero;
									}
								}

								if (!empty($rastreador) && $config->isSaveTracking($record->getCreatedAt())) {
									$record->setTrackingNumber(trim($rastreador));
									$record->setStatus($response->situacao);

									$record->save();

									Mage::getModel('eloom_blingv3/shipment')->createShipment($order, $rastreador, $shippingMethodCode);
								}
							}
						} catch (Exception $e) {
							$this->logger->error($e->getMessage());
						}
					}
				} catch (Exception $e) {
					$this->logger->error($e->getMessage());
				}
			}

			$pageValue++;
		}
	}

	public function completeNfe() {
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('access_key', array('null' => true));
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-1 day', time()), 'to' => time(), 'datetime' => true));
		$collection->setOrder('entity_id', 'DESC');

		if (!$collection->getSize()) {
			return;
		}
		$config = Mage::getModel('eloom_blingv3/config');
		$comment = $config->getNfeOutComment();

		$totalRecords = $collection->getSize();
		$num = ($totalRecords / self::PAGE_RESULTS);
		$offset = 0;
		$pageValue = 1;

		$bling = Bling::of(null, null, $config->getAccessToken());

		for ($i = 0; $i < $num; $i++) {
			if ($pageValue > 1) {
				$offset = ($pageValue - 1) * self::PAGE_RESULTS;
			}

			$collection = $this->getAccessKeyCollection(self::PAGE_RESULTS, $offset);
			if (!$collection->getSize()) {
				break;
			}

			foreach ($collection as $record) {
				$this->logger->info(sprintf("Bling V3 - Buscando NF %s do Pedido %s", $record->getBlingNumber(), $record->getOrderId()));
				try {
					$response = $bling->nfe()->find($record->getBlingId());
					/*
					if ($response && isset($response->error) && $response->error->fields[0]->cod == 14) {
						$record->delete();
						continue;
					}
					*/

					if ($response) {
						$chaveAcesso = $response->chaveAcesso;
						if ($chaveAcesso) {
							$record->setAccessKey($chaveAcesso);
							if (!empty($comment)) {
								$comment = sprintf($comment, $chaveAcesso);
								$record->addStatusHistoryComment($comment);
							}
							$record->setDanfeUrl($response->linkDanfe);
							$record->setStatus($response->situacao);
							$record->save();
						}
					}
				} catch (Exception $e) {
					$this->logger->error($e->getMessage());
				} finally {
					$chaveAcesso = $record->getNfeAccessKey();
					if (!empty($chaveAcesso)) {
						Mage::dispatchEvent('eloom_marketplace_nfe_save', array('order_id' => $record->getOrderId(), 'nfe_number' => $record->getNfeNumber(), 'nfe_access_key' => $record->getNfeAccessKey()));
					}
				}
			}

			$pageValue++;
		}
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