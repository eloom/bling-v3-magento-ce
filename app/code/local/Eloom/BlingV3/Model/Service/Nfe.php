<?php

##eloom.licenca##

use Eloom\SdkBling\Bling;
use Eloom\SdkBling\Enum\TipoPessoa;
use Eloom\SdkBling\Model\Request\Item;
use Eloom\SdkBling\Model\Request\Nfe;

class Eloom_BlingV3_Model_Service_Nfe extends Mage_Core_Model_Abstract {

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

	public function processNfeOut() {
		$this->logger->info("Processando Nfe.");

		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('access_key', array('null' => true));
		$collection->setOrder('entity_id', 'DESC');
		$collection->addFieldToFilter('created_at', array('from' => strtotime('-1 day', time()), 'to' => time(), 'datetime' => true));
		//$collection->getSelect()->limit(100);

		if (!$collection->getSize()) {
			$this->logger->info("Não há Nfe para enviar ao Bling.");
			return;
		}

		$config = Mage::getModel('eloom_blingv3/config');

		/**
		 * Refresh Token
		 */
		$client = Bling::of($config->getApiKey(), $config->getSecretKey(), null);
		$response = $client->refreshToken($config->getRefreshToken());
		$config->saveAccessToken($response->access_token);
		$config->saveRefreshToken($response->refresh_token);

		/**
		 * Autentica novamente
		 */
		$client = Bling::of(null, null, $config->getAccessToken());

		$nt = $client->naturezaOperacoes();
		$filtros = [
			'situacao' => 1,
			'descricao' => 'Venda de mercadorias'
		];

		$data = $nt->findAll(0, 100, $filtros);
		$this->logger->info($data);

		$helper = Mage::helper('eloombootstrap');

		foreach ($collection as $nfe) {
			try {
				$order = Mage::getModel('sales/order')->load($nfe->getOrderId());
				$this->logger->info(sprintf("Bling - Buscando NFe do pedido [%s]", $nfe->getOrderId()));

				//$dataOperacao = Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime(time()));
				$nfe = Nfe::build();
				$nfe->setTipo(1);
				//$nfe->setNumero(6541);
				//$nfe->setDataEmissao(new DateTime());
				$nfe->setDataOperacao(new DateTime());
				$nfe->setDesconto($this->getExtraAmountValues($order));

				/**
				 * Contato
				 */
				$address = $this->getAddress($order);

				$contato = $nfe->getContato();
				$contato->setContribuinte(2);
				if (null != $order->getCustomerId()) {
					$contato->setId($order->getCustomerId());
				}
				$contato->setNome($order->getCustomerName());
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
				$servicos = null;
				if (count($labels) > 0) {
					if (array_key_exists($shippingMethod, $labels)) {
						$transportadora = $labels[$shippingMethod]['bling_carrier'];
						$servicos = $labels[$shippingMethod]['bling_service'];
					}
				}

				$transporte = $nfe->getTransporte();
				$transporte->setFretePorConta(0);
				$transporte->setFrete(round($order->getBaseShippingAmount(), 2));

				$transportador = $transporte->getTransportador();
				$transportador->setNome($transportadora);
				$transporte->setTransportador($transportador);

				if ($servicos) {
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

				$nfe->setTransporte($transporte);

				$this->logger->info($nfe->jsonSerialize());
			} catch (\Exception $e) {
				$this->logger->error(sprintf("Erro ao gerar Nfe, pedido [%s].", $nfe->getOrderId()));
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
			$addition = $order->getBaseTaxAmount();
		}
		$amount = $addition + $discount;

		return abs(round($amount, 2));
	}
}