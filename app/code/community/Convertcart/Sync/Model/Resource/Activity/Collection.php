<?php
class Convertcart_Sync_Model_Resource_Activity_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('convertcart_sync/activity');
    }
}