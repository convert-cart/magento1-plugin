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

        $client_id = Mage::Helper('convertcart_analytics')->getKey();
        if(!isset($client_id))
            return ;

        $script = Mage::app()->getLayout()->createBlock('core/template')
                  ->setClientId($client_id)
                  ->setTemplate('convertcart/init.phtml');
        return $script;
    }

    public function getData(){
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false) //dont proceed if not enabled
            return;

        $session        = $this->_getSession();
        $cc_event_data = $session->getCc_Events();

        if(empty($cc_event_data))
            return;

        return $cc_event_data;
	}

    public function insertmeta(){
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false)
            return;

        $meta_data = array();
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $meta_data['customer_status'] = 'logged_in';
            $meta_data['customer_email'] = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }
        else
            $meta_data['customer_status'] = 'guest';

        $meta_data['current_currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $meta_data['language'] = Mage::app()->getLocale()->getLocaleCode();
        $meta_data['base_currency'] = Mage::app()->getStore()->getBaseCurrencyCode();

        $meta_data['magento_store_code'] = Mage::app()->getStore()->getCode();
        $meta_data['magento_website_code'] = Mage::app()->getWebsite()->getCode();
        $meta_data['magento_store_id'] = Mage::app()->getStore()->getId();
        $meta_data['magento_website_id'] = Mage::app()->getWebsite()->getId();

        $meta_data['platform'] = "Magento";
        $meta_data['platform_version'] = Mage::getVersion();     

        return $meta_data;
    }

	public function storeData($event_data)
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false)
            return;

        $session = $this->_getSession();
        $cc_data = $session->getCc_Events();

        if(!$cc_data or empty($cc_data)){
            $cc_data = array();
            $cc_data[] = $event_data;
        }
        else
            $cc_data[] = $event_data;

        $session->setCc_Events($cc_data);
        return $this;
	}
    
    public function clearData()
    {
        Mage::getSingleton('convertcart_analytics/session')
        ->setCc_Events(array());
        return $this;
    }

}