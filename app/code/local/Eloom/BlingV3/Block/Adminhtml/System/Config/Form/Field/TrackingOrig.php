<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_TrackingOrig extends Mage_Core_Block_Html_Select {

	const NF_NUMBER = 'nf_number';

	const TRACKING_NUMBER = 'tracking_number';

	public function _toHtml() {
		$this->addOption(self::NF_NUMBER, 'Número da Nota Fiscal');
		$this->addOption(self::TRACKING_NUMBER, 'Código de Rastreamento');

		return parent::_toHtml();
	}

	public function setInputName($value) {
		return $this->setName($value);
	}

}