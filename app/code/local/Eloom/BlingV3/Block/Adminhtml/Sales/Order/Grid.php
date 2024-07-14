<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid {

	protected function _prepareMassaction() {
		parent::_prepareMassaction();

		$this->getMassactionBlock()->addItem(
			'eloom_blingv3_nfe_out', array('label' => $this->__('Emitir Nf-e - V3 (Saída)'),
				'url' => $this->getUrl('eloom_blingv3/adminhtml_nfe/sendNfe')
			)
		);
	}

}
