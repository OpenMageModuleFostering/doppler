<?php
/**
 * 0.1.3 - 0.1.4 upgrade installer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

$installer = $this;

$installer->startSetup();

if (!$installer->tableExists($installer->getTable('makingsense_doppler/doppler_defaultlist'))) {
	$installer->run("
		CREATE TABLE `{$installer->getTable('makingsense_doppler/doppler_defaultlist')}` (
		  `id` int(11) unsigned NOT NULL,
		  `listId` int(11) unsigned NOT NULL,
		  `name` varchar(255) DEFAULT NULL,
		  `list_status` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
}

$installer->endSetup();