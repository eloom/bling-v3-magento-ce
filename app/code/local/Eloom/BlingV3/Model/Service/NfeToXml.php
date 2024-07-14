<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Service_NfeToXml extends Mage_Core_Model_Abstract {

	const ATTR_OPERATION_UNIT = 'nfe_operation_unit';
	const ATTR_PRODUCT_SOURCE = 'nfe_product_source';
	const ATTR_NCM = 'nfe_ncm';
	const ATTR_CEST = 'nfe_cest';
	const ATTR_GTIN = 'nfe_gtin';

	protected $order;
	protected $dom;
	protected $config;

	/**
	 * Initialize resource model
	 */
	protected function _construct() {
		$this->config = Mage::getModel('eloom_blingv3/config');
		parent::_construct();
	}

	public function parseXml($order) {
		$this->order = $order;

		$this->dom = new DomDocument('1.0', 'UTF-8');
		/**
		 * pedido
		 */
		$nfe = $this->appendPedido();
		/**
		 * clientes
		 */
		$cliente = $this->appendCliente($nfe);
		$nfe->appendChild($cliente);

		/**
		 * itens
		 */
		$itens = $this->appendItens($nfe);
		$nfe->appendChild($itens);

		/**
		 * Transport
		 */
		$transport = $this->appendTransport($nfe);
		$nfe->appendChild($transport);

		/**
		 * Parcelas
		 */
		$installments = Mage::getModel('eloom_blingv3/service_payment_proxy')->parseXml($this->order->getId(), $this->order->getPayment());
		if ($installments) {
			$parcelasChild = $this->appendParcelas($nfe, $installments);
			$nfe->appendChild($parcelasChild);
		}

		/**
		 * Outras Despesas
		 */
		$despesas = $this->appendOutrasDespesas($nfe);
		$nfe->appendChild($despesas);

		$this->dom->appendChild($nfe);

		return trim($this->dom->saveXML());
	}

	protected function appendParcelas($nfe, $installments) {
		$parcelas = $nfe->appendChild($this->dom->createElement('parcelas'));
		foreach($installments as $paymentMethod) {
			$parcela = $parcelas->appendChild($this->dom->createElement('parcela'));
			if ($paymentMethod->getDays()) {
				$parcela->appendChild($this->dom->createElement('dias', $paymentMethod->getDays()));
			}
			if ($paymentMethod->getPaymentDay()) {
				$parcela->appendChild($this->dom->createElement('data', $paymentMethod->getPaymentDay()));
			}
			if ($paymentMethod->getAmount()) {
				$parcela->appendChild($this->dom->createElement('vlr', $paymentMethod->getAmount()));
			}
			if ($paymentMethod->getMethod()) {
				$parcela->appendChild($this->dom->createElement('forma', $paymentMethod->getMethod()));
			}
			if ($paymentMethod->getObservations()) {
				$parcela->appendChild($this->dom->createElement('obs', $paymentMethod->getObservations()));
			}

			$parcelas->appendChild($parcela);
		}

		return $parcelas;
	}

	protected function appendTransport($nfe) {
		$shippingMethod = $this->order->getShippingMethod();
		$shippingMethods = unserialize($this->config->getShippingMapped());
		$labels = null;

		if (is_array($shippingMethods)) {
			$labels = array();
			foreach($shippingMethods as $value) {
				$labels[$value['method']] = $value;
				if ($value['method'] == $shippingMethod) {
					break;
				}
			}
		}

		$transportadora = null;
		$servicos = null;
		if (is_array($labels)) {
			if (array_key_exists($shippingMethod, $labels)) {
				$transportadora = $labels[$shippingMethod]['bling_carrier'];
				$servicos = $labels[$shippingMethod]['bling_service'];
			}
		}

		$transporte = $nfe->appendChild($this->dom->createElement('transporte'));
		$transporte->appendChild($this->dom->createElement('transportadora', $transportadora));
		$transporte->appendChild($this->dom->createElement('tipo_frete', 'R'));
		if ($servicos) {
			$transporte->appendChild($this->dom->createElement('servico_correios', $servicos));

			// etiquetas
			$address = $this->getAddress();

			$etiqueta = $transporte->appendChild($this->dom->createElement('dados_etiqueta'));
			$helper = Mage::helper('eloom_platform');
			$etiqueta->appendChild($this->dom->createElement('nome', $address->getName()));

			if (!$helper->isEmpty($address->getStreet(1))) {
				$etiqueta->appendChild($this->dom->createElement('endereco', $address->getStreet(1)));
			}
			if (!$helper->isEmpty($address->getStreet(2))) {
				$etiqueta->appendChild($this->dom->createElement('numero', $address->getStreet(2)));
			}
			if (!$helper->isEmpty($address->getStreet(3))) {
				$etiqueta->appendChild($this->dom->createElement('complemento', $address->getStreet(3)));
			}
			if (!$helper->isEmpty($address->getStreet(4))) {
				$etiqueta->appendChild($this->dom->createElement('bairro', $address->getStreet(4)));
			}
			if (!$helper->isEmpty($address->getCity())) {
				$etiqueta->appendChild($this->dom->createElement('municipio', $address->getCity()));
			}
			if (!$helper->isEmpty($address->getRegion())) {
				$etiqueta->appendChild($this->dom->createElement('uf', $address->getRegionCode()));
			}
			if (!$helper->isEmpty($address->getPostcode())) {
				$etiqueta->appendChild($this->dom->createElement('cep', $address->getPostcode()));
			}
			$transporte->appendChild($etiqueta);
		}

		return $transporte;
	}

	/**
	 * Gets extra amount values for order
	 *
	 * @return float
	 */
	protected function getExtraAmountValues() {
		$addition = 0.00;
		$discount = 0.00;
		if ($this->order->getBaseDiscountAmount()) {
			$discount += $this->order->getBaseDiscountAmount();
		}
		if ($this->order->getMercadopagoBaseDiscountAmount()) {
			$discount += $this->order->getMercadopagoBaseDiscountAmount();
		}
		if ($this->order->getMercadopagoBaseCampaignAmount()) {
			$discount += $this->order->getMercadopagoBaseCampaignAmount();
		}
		if ($this->order->getPayuBaseDiscountAmount()) {
			$discount += $this->order->getPayuBaseDiscountAmount();
		}
		if ($this->order->getPagseguroBaseDiscountAmount()) {
			$discount += $this->order->getPagseguroBaseDiscountAmount();
		}
		if ($this->order->getBaseAffiliateplusDiscount()) {
			$discount += $this->order->getBaseAffiliateplusDiscount();
		}
		if ($this->order->getBaseTaxAmount()) {
			$addition = $this->order->getBaseTaxAmount();
		}
		$amount = $addition + $discount;

		return abs(round($amount, 2));
	}

	protected function getInterestAmount() {
		$interest = 0.00;
		if ($this->order->getMercadopagoBaseInterestAmount()) {
			$interest += $this->order->getMercadopagoBaseInterestAmount();
		}
		if ($this->order->getPagseguroBaseInterestAmount()) {
			$interest += $this->order->getPagseguroBaseInterestAmount();
		}
		if ($this->order->getPayuBaseInterestAmount()) {
			$interest += $this->order->getPayuBaseInterestAmount();
		}

		return abs(round($interest, 2));
	}

	protected function appendCliente($nfe) {
		$helper = Mage::helper('eloom_platform');
		$cliente = $nfe->appendChild($this->dom->createElement('cliente'));
		$cliente->appendChild($this->dom->createElement('nome', $this->order->getCustomerName()));

		$tipoPessoa = Eloom_BlingV3_Enum_TipoPessoa::FISICA;
		if ($this->order->getCustomerTipoPessoa() != null && $this->order->getCustomerTipoPessoa() == Eloom_BlingV3_Enum_TipoPessoa::JURIDICA) {
			$tipoPessoa = Eloom_BlingV3_Enum_TipoPessoa::JURIDICA;
		}
		$cliente->appendChild($this->dom->createElement('tipoPessoa', $tipoPessoa));

		/**
		 * CPF
		 */
		$cpfCnpj = $this->order->getCustomerTaxvat();
		$cpfCnpj = $helper->getOnlyNumbers($cpfCnpj);
		$cliente->appendChild($this->dom->createElement('cpf_cnpj', $cpfCnpj));

		$address = $this->getAddress();

		if (!$helper->isEmpty($address->getStreet(1))) {
			$cliente->appendChild($this->dom->createElement('endereco', $address->getStreet(1)));
		}
		if (!$helper->isEmpty($address->getStreet(2))) {
			$cliente->appendChild($this->dom->createElement('numero', $address->getStreet(2)));
		}
		if (!$helper->isEmpty($address->getStreet(3))) {
			$cliente->appendChild($this->dom->createElement('complemento', $address->getStreet(3)));
		}
		if (!$helper->isEmpty($address->getStreet(4))) {
			$cliente->appendChild($this->dom->createElement('bairro', $address->getStreet(4)));
		}
		if (!$helper->isEmpty($address->getCity())) {
			$cliente->appendChild($this->dom->createElement('cidade', trim($address->getCity())));
		}
		if (!$helper->isEmpty($address->getRegion())) {
			$cliente->appendChild($this->dom->createElement('uf', $address->getRegionCode()));
		}
		if (!$helper->isEmpty($address->getPostcode())) {
			$cliente->appendChild($this->dom->createElement('cep', $address->getPostcode()));
		}

//$cliente->appendChild($this->dom->createElement('fone', ));
		$cliente->appendChild($this->dom->createElement('email', $this->order->getCustomerEmail()));

		return $cliente;
	}

	protected function appendItens($nfe) {
		$itens = $nfe->appendChild($this->dom->createElement('itens'));
		$productId = $this->config->getProductId();

		foreach($this->order->getAllVisibleItems() as $orderItem) {
			$_product = Mage::getModel('catalog/product')->load($orderItem->product_id);

			$qtd = $orderItem->getQtyOrdered();
			$basePrice = round($orderItem->getBasePrice(), 2);
			if (!empty($qtd) && $basePrice > 0) {

				$name = $orderItem->getName();
				if ($orderItem->getProductType() == 'configurable') {
					$options = unserialize($orderItem->getData('product_options'));
					$name .= '-';

					foreach($options['attributes_info'] as $attr) {
						$name .= $attr['value'] . ',';
					}
					$name = trim($name, ',');
				}
				$additionalInformations = null;
				if ($orderItem->getProductType() == 'simple') {
					$options = unserialize($orderItem->getData('product_options'));
					if (is_array($options) && count($options) && isset($options['options'])) {
						foreach ($options['options'] as $option) {
							$additionalInformations[] = sprintf("%s - %s",$option['label'], $option['value']);
						}
					}
				}
				$s = explode('-', $_product->getAttributeText(self::ATTR_PRODUCT_SOURCE));
				$source = preg_replace('/\D/', '', $s[0]);

				$name = Mage::helper('core')->escapeHtml($name);
				$item = $itens->appendChild($this->dom->createElement('item'));
				$item->appendChild($this->dom->createElement('codigo', $orderItem->getData($productId)));
				$item->appendChild($this->dom->createElement('descricao', substr($name, 0, 255)));
				$item->appendChild($this->dom->createElement('un', $_product->getAttributeText(self::ATTR_OPERATION_UNIT)));
				if(null != $additionalInformations) {
					$item->appendChild($this->dom->createElement('informacoes_adicionais', implode(', ', $additionalInformations)));
				}

				/**
				 * CEST
				 */
				$cest = $_product->getData(self::ATTR_CEST);
				if (!empty($cest)) {
					$item->appendChild($this->dom->createElement('cest', $cest));
				}
				$item->appendChild($this->dom->createElement('class_fiscal', $_product->getData(self::ATTR_NCM)));
				$item->appendChild($this->dom->createElement('origem', $source));
				$item->appendChild($this->dom->createElement('gtin', $_product->getData(self::ATTR_GTIN)));

				$item->appendChild($this->dom->createElement('qtde', $qtd));
				$item->appendChild($this->dom->createElement('vlr_unit', $basePrice));
				$item->appendChild($this->dom->createElement('tipo', 'P'));

				$itens->appendChild($item);
			}
		}

		return $itens;
	}

	protected function getAddress() {
		$address = null;
		if ($this->order->getIsVirtual()) {
			$address = $this->order->getBillingAddress();
		} else {
			$address = $this->order->getShippingAddress();
		}

		return $address;
	}

}
