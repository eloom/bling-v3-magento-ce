<?php

##eloom.licenca##

$installer = $this;
$installer->startSetup();
$conn = $installer->getConnection();

if (!$conn->tableColumnExists($this->getTable('eloom_blingv3_nfe'), 'danfe_url')) {
	$installer->run("ALTER TABLE {$this->getTable('eloom_blingv3_nfe')} ADD `danfe_url` TEXT NULL");
}
$installer->endSetup();
