<?php
/**
 * 0.1.4 - 0.1.5 upgrade installer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

$installer = $this;
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId(Mage_Catalog_Model_Category::ENTITY);
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('customer', 'status_doppler_sync', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Doppler Export Status',
    'default'       => 'Pending',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 0
));

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'status_doppler_sync',
    '999'
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'status_doppler_sync');
$oAttribute->save();

$installer->endSetup();
