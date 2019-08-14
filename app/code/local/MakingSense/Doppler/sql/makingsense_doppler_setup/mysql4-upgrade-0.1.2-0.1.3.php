<?php
/**
 * 0.1.2 - 0.1.3 upgrade installer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

$installer = $this;

$installer->startSetup();

if (!$installer->tableExists($installer->getTable('makingsense_doppler/doppler_lists'))) {
	$installer->run("
		CREATE TABLE `{$installer->getTable('makingsense_doppler/doppler_lists')}` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `list_id` int(11) unsigned NOT NULL,
		  `name` varchar(255) DEFAULT NULL,
		  `status` varchar(255) DEFAULT NULL,
		  `subscribers_count` int(11) unsigned NOT NULL,
		  `last_usage` datetime DEFAULT NULL,
		  `creation_date` datetime DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
}
	
$installer->endSetup();