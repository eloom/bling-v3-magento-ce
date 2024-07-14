<?php

##eloom.licenca##

class Eloom_BlingV3_Model_System_Config_Source_ProductId {

	public function toOptionArray() {
		$helper = Mage::helper('eloom_blingv3');

		return array(
			//array('value' => 'product_id', 'label' => $helper->__('ID')),
			array('value' => 'sku', 'label' => $helper->__('SKU')),
		);
	}

}
