<?php

##eloom.licenca##

class Eloom_BlingV3_Model_System_Config_Source_CarrierToTrack {

	public function toOptionArray() {
		$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
		$data = array();
		foreach($methods as $_code => $_method) {
			if (!$_title = Mage::getStoreConfig("carriers/$_code/title"))
				$_title = $_code;

			$data[] = array('value' => $_code, 'label' => $_title);
		}

		return $data;
	}

}
