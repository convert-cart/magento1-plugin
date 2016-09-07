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

        $clientId = Mage::Helper('convertcart_analytics')->getKey();
        if(!isset($clientId))
            return ;

        $script = Mage::app()->getLayout()->createBlock('core/template')
                  ->setClientId($clientId)
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
        if ( Mage::getSingleton('customer/session')->isLoggedIn() )
        {
            $metaData['customer_status'] = 'logged_in';
            $metaData['customer_email'] = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }
        else
            $metaData['customer_status'] = 'guest';

        $metaData['current_currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $metaData['language'] = Mage::app()->getLocale()->getLocaleCode();
        $metaData['base_currency'] = Mage::app()->getStore()->getBaseCurrencyCode();

        $metaData['magento_store_code'] = Mage::app()->getStore()->getCode();
        $metaData['magento_website_code'] = Mage::app()->getWebsite()->getCode();
        $metaData['magento_store_id'] = Mage::app()->getStore()->getId();
        $metaData['magento_website_id'] = Mage::app()->getWebsite()->getId();

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

        if(!$ccData or empty($ccData)){
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