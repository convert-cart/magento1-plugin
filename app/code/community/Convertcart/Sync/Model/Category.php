<?php
class Convertcart_Sync_Model_Category extends Mage_Catalog_Model_Category
{
    protected function _construct()
    {
        $this->_init('catalog/category');
    }
}