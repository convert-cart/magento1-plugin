<?php
$installer = $this;
 
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('convertcart_sync/activity'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id'
    )
    ->addColumn(
        'action', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Action'
    )
    ->addColumn(
        'type', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Type'
    )
    ->addColumn(
        'item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'ItemId'
    )
    ->addColumn(
        'parent_ids', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'ParentIds'
    )
    ->addColumn(
        'children_ids', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'ChildrenIds'
    );
$installer->getConnection()->createTable($table);
$installer->endSetup();