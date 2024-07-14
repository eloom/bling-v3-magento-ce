<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Service_Nfe_OutToXml extends Eloom_BlingV3_Model_Service_NfeToXml {

	/**
	 * Initialize resource model
	 */
	protected function _construct() {
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

		$this->dom->appendChild($nfe);

		return trim($this->dom->saveXML());
	}

	private function appendPedido() {
		$helper = Mage::helper('eloom_platform');
		$nfe = $this->dom->appendChild($this->dom->createElement('pedido'));
		$nfe->appendChild($this->dom->createElement('numero', $this->order->getIncrementId()));
		$nfe->appendChild($this->dom->createElement('numero_loja', $this->order->getIncrementId()));
		$nfe->appendChild($this->dom->createElement('tipo', Eloom_BlingV3_Enum_TipoNota::SAIDA));

		$blingSore = $this->config->getStoreNumber();
		if (!$helper->isEmpty($blingSore)) {
			$nfe->appendChild($this->dom->createElement('loja', $blingSore));
		}
		$natOp = $this->config->getNfeOutNatOpByStatus($this->order->getStatus());
		$nfe->appendChild($this->dom->createElement('nat_operacao', $natOp));

		$nfe->appendChild($this->dom->createElement('vlr_frete', round($this->order->getBaseShippingAmount(), 2)));
		$nfe->appendChild($this->dom->createElement('vlr_desconto', $this->getExtraAmountValues()));

		// juros
		$interest = $this->getInterestAmount();
		$nfe->appendChild($this->dom->createElement('vlr_despesas', $interest));

		$obs = array();
		if ($interest > 0) {
			$obs[] = sprintf("%s lançados em 'Outras Despesas' se refere a juros do parcelamento.", Mage::helper('core')->currency($interest, true, false));
		}

		$nfe->appendChild($this->dom->createElement('obs', implode("\n", $obs)));

		return $nfe;
	}

}
