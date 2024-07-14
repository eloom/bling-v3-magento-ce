<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_Renderer_Actions extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

	public function render(Varien_Object $row) {
		$data = $row->getData();
		$links = '';
		if (isset($data['danfe_url'])) {
			$links .= '<a href="' . $data['danfe_url'] . '" target="blank">DANFE</a>';
		}

		return $links;
	}
}