<?php
/**
 * ConvertCart Setup Helper
 * 
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Setup extends Mage_Core_Helper_Abstract
{
    /**
     * Current module version
     */
    const MODULE_VERSION = '1.5.0';

    /**
     * Run post-installation setup
     */
    public function runPostInstall()
    {
        $this->verifyDirectories();
        $this->verifyCronJobs();
        $this->verifyApiKey();
        $this->updateModuleVersion();
    }

    /**
     * Verify and create required directories
     */
    protected function verifyDirectories()
    {
        $varDir = Mage::getBaseDir('var') . DS . 'convertcart';
        if (!file_exists($varDir)) {
            mkdir($varDir, 0777, true);
        }

        // Add more directory checks as needed
    }

    /**
     * Verify and setup cron jobs
     * No cron jobs needed as of version 1.5.0
     */
    protected function verifyCronJobs()
    {
        // Cron jobs removed in favor of real-time tracking
    }

    /**
     * Generate API key if not exists
     */
    protected function verifyApiKey()
    {
        $apiKey = Mage::getStoreConfig('convertcart/sync/api_key');
        if (empty($apiKey)) {
            $apiKey = md5(uniqid(rand(), true));
            Mage::getModel('core/config')
                ->saveConfig('convertcart/sync/api_key', $apiKey);
        }
    }

    /**
     * Update module version in config
     */
    protected function updateModuleVersion()
    {
        $currentVersion = Mage::getConfig()->getNode('modules/Convertcart/version');
        if ((string)$currentVersion !== self::MODULE_VERSION) {
            Mage::getModel('core/config')
                ->saveConfig('convertcart/version', self::MODULE_VERSION);
        }
    }
}
