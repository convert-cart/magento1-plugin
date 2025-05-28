<?php
class Convertcart_Model_Sync_Category extends Mage_Catalog_Model_Category
{
    protected function _construct()
    {
        $this->_init('convertcart/sync_cc_category');
    }
}