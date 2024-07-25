<?php

##eloom.licenca##

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
}