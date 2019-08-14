<?php
/**
 * 0.1.5- 0.1.6 upgrade installer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

$installer = $this;

$installer->startSetup();

if (!$installer->tableExists($installer->getTable('makingsense_doppler/doppler_importtasks'))) {
	$table = $installer->getConnection()
		->newTable($installer->getTable('makingsense_doppler/doppler_importtasks'))
		->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'identity'  => true,
			'unsigned'  => true,
			'nullable'  => false,
			'primary'   => true,
		), 'ID')
		->addColumn('import_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'Import ID')
		->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'Status')
		->addColumn('creation', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
			'nullable'  => false,
		), 'Created At')
		->addColumn('customers', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
		), 'Customers');

	$installer->getConnection()->createTable($table);
}

$installer->endSetup();