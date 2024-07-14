<?php

##eloom.licenca##

$installer = $this;
$installer->startSetup();
$conn = $installer->getConnection();

$attribute = Mage::getResourceModel('catalog/eav_mysql4_setup', 'core_setup');

$ncm = $attribute->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'nfe_ncm');
if ($ncm === false) {
	$this->addAttribute('catalog_product', 'nfe_ncm', array(
		'group' => 'NF-e',
		'type' => 'varchar',
		'input' => 'text',
		'label' => 'NCM',
		'source' => '',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible' => true,
		'required' => true,
		'user_defined' => false,
		'default' => 0,
		'visible_on_front' => false,
		'is_configurable' => false,
		'sort_order' => 1
	));
}

$cest = $attribute->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'nfe_cest');
if ($cest === false) {
	$this->addAttribute('catalog_product', 'nfe_cest', array(
		'group' => 'NF-e',
		'type' => 'varchar',
		'input' => 'text',
		'label' => 'CEST',
		'source' => '',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible' => true,
		'required' => false,
		'user_defined' => false,
		'default' => 0,
		'visible_on_front' => false,
		'is_configurable' => false,
		'sort_order' => 2
	));
}

$gtin = $attribute->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'nfe_gtin');
if ($gtin === false) {
	$this->addAttribute('catalog_product', 'nfe_gtin', array(
		'group' => 'NF-e',
		'type' => 'varchar',
		'input' => 'text',
		'label' => 'Gtin',
		'source' => '',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible' => true,
		'required' => false,
		'user_defined' => false,
		'default' => 0,
		'visible_on_front' => false,
		'is_configurable' => false,
		'sort_order' => 3
	));
}

$operationUnit = $attribute->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'nfe_operation_unit');
if ($operationUnit === false) {
	$installer->addAttribute('catalog_product', 'nfe_operation_unit', array(
		'group' => 'NF-e',
		'label' => 'Unidade de Medida',
		'note' => '',
		'type' => 'varchar',
		'input' => 'select',
		'frontend_class' => '',
		'source' => '',
		'backend' => '',
		'frontend' => '',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'required' => true,
		'visible_on_front' => false,
		'apply_to' => 'simple',
		'is_configurable' => false,
		'used_in_product_listing' => false,
		'sort_order' => 4,
		'option' => array(
			'values' => array(
				0 => 'UN',
				1 => 'CX',
				2 => 'PC'
			),
		)
	));
}

$productSource = $attribute->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'nfe_product_source');
if ($productSource === false) {
	$installer->addAttribute('catalog_product', 'nfe_product_source', array(
		'group' => 'NF-e',
		'label' => 'Origem',
		'note' => '',
		'type' => 'int',
		'input' => 'select',
		'frontend_class' => '',
		'source' => '',
		'backend' => '',
		'frontend' => '',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'required' => true,
		'visible_on_front' => false,
		'apply_to' => 'simple',
		'is_configurable' => false,
		'used_in_product_listing' => false,
		'sort_order' => 5,
		'option' => array(
			'values' =>
				array(
					0 => '0 - Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8',
					1 => '1 - Estrangeira - Importação direta, exceto a indicada no código 6',
					2 => '2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7',
					3 => '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%',
					4 => '4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos Ajustes',
					5 => '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%',
					6 => '6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX',
					7 => '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante em lista da CAMEX',
					8 => '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%'
				)
		)
	));
}

$nfeTable = $installer->getTable('eloom_blingv3_nfe');
if ($installer->getConnection()->isTableExists($nfeTable) != true) {
	$table = $installer->getConnection()->newTable($nfeTable)
		->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 10, array(
			'identity' => true,
			'unsigned' => true,
			'nullable' => false,
			'primary' => true,
		), 'ID')
		->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 10, array(
			'nullable' => false,
		), 'Order ID')
		->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 20, array(
			'nullable' => false,
		), 'Placed from Store')
		->addColumn('bling_number', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
			'nullable' => false,
		), 'Nfe BlingV3 Number')
		->addColumn('tracking_number', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
			'nullable' => true,
		), 'Tracking Number')
		->addColumn('bling_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
			'nullable' => false,
		), 'Nfe BlingV3 ID')
		->addColumn('access_key', Varien_Db_Ddl_Table::TYPE_TEXT, 80, array(
			'nullable' => true,
			'default' => null,
		), 'Nfe Access Key')
		->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 80, array(
			'nullable' => true,
			'default' => null,
		), 'Nfe Status')
		->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, 0, array(
			'nullable' => false,
		), 'NFe creation date')
		->addColumn('comments', Varien_Db_Ddl_Table::TYPE_TEXT, 5000, array(
			'nullable' => false,
		), 'NFe Comments');

	$installer->getConnection()->createTable($table);
}
$installer->endSetup();
