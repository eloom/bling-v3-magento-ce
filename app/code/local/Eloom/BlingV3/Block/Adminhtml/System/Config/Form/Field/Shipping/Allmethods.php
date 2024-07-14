<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_Shipping_Allmethods extends Mage_Core_Block_Html_Select {

	public function _toHtml() {
		$options = Mage::getSingleton('adminhtml/system_config_source_shipping_allmethods')->toOptionArray();

		foreach($options as $option) {
			$this->addOption($option['value'], $option['label']);
		}

		return parent::_toHtml();
	}

	public function setInputName($value) {
		return $this->setName($value);
	}

}
