<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Nfe extends Mage_Core_Model_Abstract {

	const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	protected function _construct() {
		parent::_construct();
		$this->_init('eloom_blingv3/nfe');
	}

	protected function _beforeSave() {
		return parent::_beforeSave();
	}

	public function create() {
		$creationAt = Mage::getSingleton('core/date')->gmtDate(self::DATE_TIME_FORMAT);
		$this->setCreatedAt($creationAt);

		return $this;
	}

	protected function _afterSave() {
		return parent::_afterSave();
	}

	public function reset() {
		$this->unsetData();

		return $this;
	}

	public function getOrder() {
		return Mage::getModel('sales/order')->load($this->getOrderId());
	}

	public function addStatusHistoryComment($comment) {
		$this->getOrder()->addStatusHistoryComment($comment)
			->setIsVisibleOnFront(false)
			->setIsCustomerNotified(false)->save();
	}

}
