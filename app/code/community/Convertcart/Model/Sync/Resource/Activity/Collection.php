<?php
class Convertcart_Model_Activity_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('convertcart/sync_cc_resource_activity');
    }
}
