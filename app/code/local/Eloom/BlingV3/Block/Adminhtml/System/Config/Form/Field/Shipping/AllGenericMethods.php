<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_Shipping_AllGenericMethods extends Mage_Core_Block_Html_Select {

	public function _toHtml() {
		$this->addOption('', '');

		$carriers = Mage::getSingleton('shipping/config')->getAllCarriers();
		foreach($carriers as $carrierCode => $carrierModel) {
			if (!$carrierModel->isActive()) {
				continue;
			}
			$carrierMethods = $carrierModel->getAllowedMethods();
			if (!$carrierMethods) {
				continue;
			}
			$carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
			$this->addOption($carrierCode, $carrierTitle);
		}

		return parent::_toHtml();
	}

	public function setInputName($value) {
		return $this->setName($value);
	}

}
