<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_Payment_Allmethods extends Mage_Core_Block_Html_Select {

	public function _toHtml() {
		$methods = Mage::helper('payment')->getPaymentMethodList(true, true, true);

		foreach($methods as $key => $value) {
			if (is_array($value['value'])) {
				foreach($value['value'] as $key2 => $method) {
					$method['label'] = '[' . $method['value'] . '] ' . $method['label'];

					$methods[$key]['value'][$key2] = $method;
				}
			} else {
				$value['label'] = '[' . $value['value'] . '] ' . $value['label'];

				$methods[$key] = $value;
			}

			$this->addOption($methods[$key]['value'], $methods[$key]['label']);
		}

		return parent::_toHtml();
	}

	public function setInputName($value) {
		return $this->setName($value);
	}

}
