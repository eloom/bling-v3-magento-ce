<?php

##eloom.licenca##

use Eloom\SdkBling\Enum\TipoPessoa;
use Eloom\SdkBling\Model\Request\Nfe;

class Eloom_BlingV3_Model_Observer {

	private $logger;

	/**
	 * Initialize resource model
	 */
	public function __construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
	}

	public function addNfeMassAction($observer) {
		$block = $observer->getEvent()->getBlock();
		if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction) {
			if ($block->getParentBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
				$block->addItem('eloom_blingv3_nfe_out', array(
					'label' => 'Emitir Nf-e - V3 (Saída)',
					'url' => Mage::app()->getStore()->getUrl('eloom_blingv3/adminhtml_nfe/sendNfe'),
				));
			}
		}
	}

	public function processCallback($observer) {
		$code = $observer->getEvent()->getCode();
		$this->logger->info(sprintf("Processando callback. Código [%s].", $code));

		//$dataOperacao = Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime(time()));
		$nfe = Nfe::of();
		$nfe->setTipo(1);
		$nfe->setNumero(6541);
		$nfe->setDataEmissao(new DateTime());
		$nfe->setDataOperacao(new DateTime());

		$contato = $nfe->getContato();
		$contato->setId('123456');
		$contato->setNome('Rodrigo Sanders');
		$contato->setTipoPessoa(TipoPessoa::FISICA);
		$contato->setContribuinte(2);
		$contato->setTelefone('5198038165');
		$contato->setRg('1078101928');
		$contato->setEmail('paulo.sanders@gmail.com');

		$endereco = $contato->getEndereco();
		$endereco->setEndereco('Rua Formosa');
		$endereco->setNumero('110');
		$endereco->setComplemento('Casa');
		$endereco->setCep('92480-000');
		$endereco->setBairro('Califórnia');
		$endereco->setMunicipio('Nova Santa Rita');
		$endereco->setUf('RS');
		$endereco->setPais('Brasil');
		$contato->setEndereco($endereco);

		$nfe->setContato($contato);

		$transporte = $nfe->getTransporte();
		$transporte->setFretePorConta(0);
		$transporte->setFrete(20);

		$veiculo = $transporte->getVeiculo();
		$veiculo->setPlaca('LDO-2373');
		$veiculo->setUf('RS');
		$veiculo->setMarca('Volvo');

		$transporte->setVeiculo($veiculo);
		$nfe->setTransporte($transporte);

		$this->logger->info($nfe->jsonSerialize());
	}
}