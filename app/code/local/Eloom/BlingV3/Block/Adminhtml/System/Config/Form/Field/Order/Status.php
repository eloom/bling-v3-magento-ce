<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_Order_Status extends Mage_Core_Block_Html_Select {

	public function _toHtml() {
		foreach(Mage::getModel('sales/order_status')->getResourceCollection()->getData() as $s) {
			$this->addOption($s['status'], $s['label']);
		}

		return parent::_toHtml();
	}

	public function setInputName($value) {
		return $this->setName($value);
	}

}
