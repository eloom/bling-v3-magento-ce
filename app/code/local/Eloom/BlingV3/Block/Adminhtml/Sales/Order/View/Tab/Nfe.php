<?php

##eloom.licenca##

class Eloom_BlingV3_Block_Adminhtml_Sales_Order_View_Tab_Nfe extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface {

	public function __construct() {
		parent::__construct();
		$this->setId('eloom_blingv3_grid');
		$this->setUseAjax(true);
		$this->setDefaultSort('entity_id', 'desc');
		$this->setFilterVisibility(false);
		$this->setPagerVisibility(false);
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection() {
		$collection = Mage::getModel('eloom_blingv3/nfe')->getCollection()->addFieldToFilter('order_id', $this->getOrder()->getId());
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {
		/*
			$this->addColumn('entity_id', array(
			'header' => $this->__('ID'),
			'width' => '50px',
			'index' => 'entity_id')
			);
			$this->addColumn('created_at', array(
			'header' => $this->__('Criado em'),
			'index' => 'created_at',
			'width' => '100px',
			'type' => 'datetime',
			));
		 */

		$this->addColumn('bling_number', array(
			'header' => $this->__('NF Nr'),
			'width' => '100px',
			'index' => 'bling_number',
		));
		$this->addColumn('access_key', array(
			'header' => $this->__('Chave'),
			'width' => '50px',
			'index' => 'access_key',
		));
		$this->addColumn('tracking_number', array(
			'header' => $this->__('Localizador'),
			'width' => '50px',
			'index' => 'tracking_number',
		));

		$this->addColumn('actions', array(
			'header' => $this->__('Ações'),
			'width' => '50px',
			'sortable' => false,
			'filter' => false,
			'renderer' => new Eloom_BlingV3_Block_Adminhtml_Renderer_Actions()
		));

		return parent::_prepareColumns();
	}

	public function getOrder() {
		return Mage::registry('current_order');
	}

	public function getSource() {
		return $this->getOrder();
	}

	public function getTabLabel() {
		return Mage::helper('sales')->__('BlingV3 - NF-e');
	}

	public function getTabTitle() {
		return Mage::helper('sales')->__('BlingV3 - NF-e');
	}

	public function canShowTab() {
		return true;
	}

	public function isHidden() {
		return false;
	}

}
