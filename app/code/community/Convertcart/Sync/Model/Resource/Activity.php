<?php
class Convertcart_Sync_Model_Resource_Activity extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('convertcart_sync/activity', 'id');
    }
}