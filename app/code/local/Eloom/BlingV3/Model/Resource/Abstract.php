<?php

##eloom.licenca##

abstract class Eloom_BlingV3_Model_Resource_Abstract extends Mage_Core_Model_Resource_Db_Abstract {

	/**
	 * Prepare data for save
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @return array
	 */
	protected function _prepareDataForSave(Mage_Core_Model_Abstract $object) {
		$currentTime = Varien_Date::now();
		if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
			$object->setCreatedAt($currentTime);
		}
		$object->setUpdatedAt($currentTime);
		$data = parent::_prepareDataForSave($object);
		return $data;
	}

}
