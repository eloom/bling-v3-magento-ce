<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_Form_Information extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

	protected function _construct() {
		parent::_construct();

		if (!$this->getTemplate()) {
			$this->setTemplate("eloom/bling_v3/form/information.phtml");
		}
	}

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$html = $this->_getHeaderHtml($element);
		$html .= $this->_toHtml();
		$html .= $this->_getFooterHtml($element);
		return $html;
	}

	public function getVersion() {
		return Mage::getConfig()->getModuleConfig('Eloom_Bling')->version;
	}

}
