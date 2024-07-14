<?php

##eloom.licenca##

class Eloom_BlingV3_Model_System_Config_Source_Timer {

	const IMMEDIATELY = 'immediately';
	const MANUALLY = 'manually';

	public function toOptionArray() {
		$helper = Mage::helper('eloom_blingv3');

		return array(
			array('value' => 'manually', 'label' => $helper->__('Será inserido manualmente')),
			array('value' => 'immediately', 'label' => $helper->__('Logo após o BlingV3 gerar')),
			array('value' => '3', 'label' => $helper->__('03 horas após gerado')),
			array('value' => '6', 'label' => $helper->__('06 horas após gerado')),
			array('value' => '9', 'label' => $helper->__('09 horas após gerado')),
			array('value' => '12', 'label' => $helper->__('12 horas após gerado')),
			array('value' => '18', 'label' => $helper->__('18 horas após gerado')),
			array('value' => '23', 'label' => $helper->__('23 horas após gerado'))
		);
	}

}
