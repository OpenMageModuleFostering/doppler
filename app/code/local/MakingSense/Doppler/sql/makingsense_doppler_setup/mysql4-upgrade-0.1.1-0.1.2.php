<?php
/**
 * 0.1.1 - 0.1.2 upgrade installer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

$installer = $this;
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId(Mage_Catalog_Model_Category::ENTITY);
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('customer', 'doppler_synced', array(
    'input'         => 'boolean',
    'type'          => 'int',
    'label'         => 'Exported to Doppler',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 0,
    'default'      => 0
));

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'doppler_synced',
    '999'
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'doppler_synced');
$oAttribute->save();

$installer->endSetup();
