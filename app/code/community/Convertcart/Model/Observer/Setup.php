<?php
/**
 * ConvertCart Setup Observer
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Model_Observer_Setup
{
    /**
     * Run post-installation setup
     *
     * @param Varien_Event_Observer $observer
     */
    public function runPostInstall($observer)
    {
        try {
            // Only run if this is a fresh install or an upgrade
            $currentVersion = Mage::getStoreConfig('convertcart/version');
            $moduleVersion = (string)Mage::getConfig()->getNode('modules/Convertcart/version');
            
            if (empty($currentVersion) || version_compare($currentVersion, $moduleVersion, '<')) {
                // Run setup helper
                Mage::helper('convertcart/setup')->runPostInstall();
                
                // Update version in config
                Mage::getConfig()->saveConfig('convertcart/version', $moduleVersion);
                
                // Clear config cache to apply changes
                Mage::app()->getCacheInstance()->cleanType('config');
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
