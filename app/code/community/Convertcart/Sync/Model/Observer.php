<?php
class Convertcart_Sync_Model_Observer
{
    public function generateKey()
    {
        Mage::Helper('convertcart_sync')->generateKey();
    }
}