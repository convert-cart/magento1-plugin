<?php
class Convertcart_Analytics_Model_Cc extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('convertcart_analytics');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('convertcart_analytics/session');
    }

    public function getInitScript()
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false) //dont proceed if not enabled
            return;

        $clientKey = Mage::Helper('convertcart_analytics')->getClientKey();
        if(!isset($clientKey))
            return ;

        $script = Mage::app()->getLayout()->createBlock('core/template')
                  ->setClientKey($clientKey)
                  ->setTemplate('convertcart/init.phtml');
        return $script;
    }

    public function getData()
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false) //dont proceed if not enabled
            return;

        $session = $this->_getSession();
        $eventData = $session->getCc_Events();

        if(empty($eventData))
            return;

        return $eventData;
    }

    public function insertMeta()
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false)
            return;

        $metaData = array();
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $metaData['customer_status'] = 'logged_in';
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if(!is_object($customer))
                return $metaData;
            $metaData['customer_email'] = $customer>getEmail();
        }
        else
            $metaData['customer_status'] = 'guest';

        $store = Mage::app()->getStore();

        if(!is_object($store))
            return $metaData;

        $metaData['current_currency'] = $store->getCurrentCurrencyCode();
        $metaData['base_currency'] = $store->getBaseCurrencyCode();

        $metaData['language'] = Mage::app()->getLocale()->getLocaleCode();

        $metaData['magento_store_code'] = $store->getCode();
        $metaData['magento_store_id'] = $store->getId();

        $website = Mage::app()->getWebsite();

        if(!is_object($website))
            return $metaData;

        $metaData['magento_website_id'] = $website->getId();
        $metaData['magento_website_code'] = $website->getCode();

        $metaData['platform'] = "Magento";
        $metaData['platform_version'] = Mage::getVersion();     

        return $metaData;
    }

    public function storeData($eventData)
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false)
            return;

        $session = $this->_getSession();
        $ccData = $session->getCc_Events();

        if (!$ccData or empty($ccData)) {
            $ccData = array();
            $ccData[] = $eventData;
        }
        else
            $ccData[] = $eventData;

        $session->setCc_Events($ccData);
        return $this;
    }
    
    public function clearData()
    {
        Mage::getSingleton('convertcart_analytics/session')
        ->setCc_Events(array());
        return $this;
    }

}