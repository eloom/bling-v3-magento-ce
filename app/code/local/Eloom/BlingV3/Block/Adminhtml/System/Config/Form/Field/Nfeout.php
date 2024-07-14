<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_Nfeout extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	protected $_initialStatus;
	protected $_finalStatus;

	public function _prepareToRender() {
		$this->addColumn('initial', array(
			'label' => Mage::helper('eloom_blingv3')->__('Status Inicial'),
			'renderer' => $this->_getInitialStatus(),
		));
		$this->addColumn('operation', array(
			'label' => Mage::helper('eloom_blingv3')->__('Natureza da Operação'),
		));
		$this->addColumn('final', array(
			'label' => Mage::helper('eloom_blingv3')->__('Status Final'),
			'renderer' => $this->_getFinalStatus(),
		));
		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('eloom_blingv3')->__('Add');
	}

	protected function _getInitialStatus() {
		if (!$this->_initialStatus) {
			$this->_initialStatus = $this->getLayout()->createBlock('eloom_blingv3/adminhtml_system_config_form_field_order_status', '', array('is_render_to_js_template' => true));
		}
		return $this->_initialStatus;
	}

	protected function _getFinalStatus() {
		if (!$this->_finalStatus) {
			$this->_finalStatus = $this->getLayout()->createBlock('eloom_blingv3/adminhtml_system_config_form_field_order_status', '', array('is_render_to_js_template' => true));
		}
		return $this->_finalStatus;
	}

	protected function _prepareArrayRow(Varien_Object $row) {
		$row->setData('option_extra_attr_' . $this->_getInitialStatus()->calcOptionHash($row->getData('initial')), 'selected="selected"');
		$row->setData('option_extra_attr_' . $this->_getFinalStatus()->calcOptionHash($row->getData('final')), 'selected="selected"');
	}

}
