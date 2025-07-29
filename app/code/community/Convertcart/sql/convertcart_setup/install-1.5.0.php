<?php
/**
 * ConvertCart installation script
 * 
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */

$installer = $this;
$installer->startSetup();

// Create activity table
$table = $installer->getConnection()
    ->newTable($installer->getTable('convertcart/sync_cc_activity'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id'
    )
    ->addColumn(
        'action', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Action'
    )
    ->addColumn(
        'type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'nullable'  => false,
        ), 'Type'
    )
    ->addColumn(
        'item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'ItemId'
    )
    ->addColumn(
        'parent_ids', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'ParentIds'
    )
    ->addColumn(
        'children_ids', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'ChildrenIds'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        'default'   => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        ), 'Created At'
    )
    ->addIndex(
        $installer->getIdxName('convertcart/sync_cc_activity', array('action')),
        array('action')
    )
    ->addIndex(
        $installer->getIdxName('convertcart/sync_cc_activity', array('type')),
        array('type')
    )
    ->addIndex(
        $installer->getIdxName('convertcart/sync_cc_activity', array('item_id')),
        array('item_id')
    )
    ->setComment('ConvertCart Activity Log');

$installer->getConnection()->createTable($table);

// Add default configuration
$configData = array(
    'convertcart/general/enabled' => 1,
    'convertcart/general/client_key' => '',
    'convertcart/sync/enabled' => 1,
    'convertcart/sync/catalog' => 1,
    'convertcart/sync/order' => 1,
    'convertcart/sync/customer' => 1,
    'convertcart/sync/category' => 1,
);

foreach ($configData as $path => $value) {
    $installer->setConfigData($path, $value);
}

// Create a new API key if not exists
$apiKey = Mage::getStoreConfig('convertcart/sync/api_key');
if (empty($apiKey)) {
    $apiKey = md5(uniqid(rand(), true));
    $installer->setConfigData('convertcart/sync/api_key', $apiKey);
}

$installer->endSetup();
