<?php
/**
 * ConvertCart upgrade script from 1.1.2 to 1.5.0
 * 
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */

$installer = $this;
$installer->startSetup();

// Check if the activity table exists and needs to be updated
$tableName = $installer->getTable('convertcart/sync_cc_activity');
$connection = $installer->getConnection();

// Add created_at column if it doesn't exist
if ($connection->tableColumnExists($tableName, 'created_at') === false) {
    $connection->addColumn(
        $tableName,
        'created_at',
        array(
            'type'      => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable'  => false,
            'default'   => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
            'comment'   => 'Created At'
        )
    );
}

// Add indexes for better performance
$connection->addIndex(
    $tableName,
    $installer->getIdxName('convertcart/sync_cc_activity', array('action')),
    array('action')
);

$connection->addIndex(
    $tableName,
    $installer->getIdxName('convertcart/sync_cc_activity', array('type')),
    array('type')
);

$connection->addIndex(
    $tableName,
    $installer->getIdxName('convertcart/sync_cc_activity', array('item_id')),
    array('item_id')
);

// Remove any existing abandoned cart configuration
$installer->deleteConfigData('convertcart/abandoned_cart/enabled');
$installer->deleteConfigData('convertcart/abandoned_cart/delay');

// Update module version
$installer->setConfigData('convertcart/version', '1.5.0');

$installer->endSetup();
