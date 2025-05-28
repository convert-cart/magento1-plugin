<?php
class Convertcart_Model_Sync_Resource_Activity extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('convertcart/sync_cc_resource_activity', 'id');
    }
}