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

    public function getCcData()
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false) //dont proceed if not enabled
            return;

        $session = $this->_getSession();
        $eventData = $session->getCc_Events();

        if(empty($eventData))
            return;

        return $eventData;
    }

    public function insertMeta($includeCustomerInfo = 0)
    {
        if(Mage::Helper('convertcart_analytics')->isEnabled() == false)
            return;

        $metaData = array();
        $metaData['date'] = gmdate('Y-m-d H:i:s');
        if ($includeCustomerInfo !=0) {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $metaData['customer_status'] = 'logged_in';
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if(!is_object($customer))
                    return $metaData;
                $metaData['customer_email'] = $customer->getEmail();
            }
            else
                $metaData['customer_status'] = 'guest';

            $store = Mage::app()->getStore();

            if(!is_object($store))
                return $metaData;

            $metaData['current_currency'] = $store->getCurrentCurrencyCode();
            $metaData['base_currency'] = $store->getBaseCurrencyCode();
            $metaData['current_currency_rate'] = $store->getCurrentCurrencyRate();

            $locale = Mage::app()->getLocale();
            if(!is_object($locale))
                $metaData['language'] = $locale->getLocaleCode();

            $metaData['magento_store_code'] = $store->getCode();
            $metaData['magento_store_id'] = $store->getId();

            $website = Mage::app()->getWebsite();

            if(!is_object($website))
                return $metaData;

            $metaData['magento_website_id'] = $website->getId();
            $metaData['magento_website_code'] = $website->getCode();
        }

        $metaData['platform'] = "Magento";
        $metaData['platform_version'] = Mage::getVersion();     

        return $metaData;
    }

    public function getCartItemOptions($item)
    {
        if(!is_object($item))
            return null;

        $helper = Mage::helper('catalog/product_configuration');
        if(!is_object($helper))
            return null;

        $options = $helper->getCustomOptions($item);
        $customOptions = array();
        foreach ($options as $option) {
            $customOption = array();
            $customOption['label'] = $option['label'];
            $customOption['value'] = $option['value'];
            $customOption['option_id'] = $option['option_id'];
            $customOption['option_type'] = $option['option_type'];
            $customOptions[] = $customOption;
        }

        return $customOptions;
    }

    public function getOrderItemOptions($item)
    {
        if(!is_object($item))
            return null;

        $options = $item->getProductOptions();

        $options = $options['options'];
        $customOptions = array();
        foreach ($options as $option) {
            $customOption = array();
            $customOption['label'] = $option['label'];
            $customOption['value'] = $option['value'];
            $customOption['option_id'] = $option['option_id'];
            $customOption['option_type'] = $option['option_type'];
            $customOptions[] = $customOption;
        }

        return $customOptions;
    }

    public function getWishlistItems()
    {
        $wishlist = array();
        $magentoVersion = Mage::getVersion();
        if ($magentoVersion) {
            $magentoVersion = explode(".", $magentoVersion);
            if ($magentoVersion[1]<=4) {
                $store = Mage::app()->getStore();
                $wishlistItems = Mage::helper('wishlist')->getItemCollection();
                foreach ($wishlistItems as $wishlistItem) {
                    $wlist['sku'] = str_replace("'", "", $wishlistItem->getSku());
                    // $wlist['quantity'] = $wishlistItem->getQty();
                    $resource = Mage::getSingleton('catalog/product');
                    if (is_object($resource)) {
                        $resource = $resource->getResource();
                        if (is_object($store) and is_object($resource)) {
                            $imagePath = $resource->getAttributeRawValue($wishlistItem->getProductId(), "image", $store);
                            $wlist['url_key'] = $resource->getAttributeRawValue($wishlistItem->getProductId(), "url_key", $store);
                        }
                        if($imagePath != null and $imagePath != "no_selection")
                            $wlist['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
                    }
                    $wishlist[] = $wlist;
                }
            } else {
                $store = Mage::app()->getStore();
                $wishlistItems = Mage::helper('wishlist')->getWishlistItemCollection();
                foreach ($wishlistItems as $wishlistItem) {
                    $product = $wishlistItem->getProduct();

                    $wlist['name'] = str_replace("'", "", $product->getName());
                    $wlist['id'] = $product->getId();
                    $wlist['quantity'] = $wishlistItem->getQty();

                    $wlist['url'] = $product->getProductUrl();

                    $resource = Mage::getSingleton('catalog/product');
                    if (is_object($resource)) {
                        $resource = $resource->getResource();
                        if (is_object($store) and is_object($resource)) {
                            $imagePath = $resource->getAttributeRawValue($product->getId(), "image", $store);
                            $wlist['sku'] = $resource->getAttributeRawValue($product->getId(), "sku", $store);
                        }
                        if($imagePath != null and $imagePath != "no_selection")
                            $wlist['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
                    }
                    $wishlist[] = $wlist;
                }
            } //else magento >= 1.5
        }
        return $wishlist;
    }

    public function customerRegisterOld()
    {
        //To support magento 1.4
        $magentoVersion = Mage::getVersion();
        if ($magentoVersion) {
            $magentoVersion = explode(".", $magentoVersion);
            if ($magentoVersion[1]>4)
                return;
        }

        //if customer logged in, then created succssfully
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) 
            return;

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if(!is_object($customer)) 
            return;

        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("customerRegister");
        $ccData['event_data'] = $customerData;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);        
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

    public function getPrice($price)
    {
        return Mage::helper('core')->currency($price, false, false);
    }

    public function getValue($number)
    {
        if ( $number == null or !isset($number) )
            return 0;
        else
            return $number;
    }
}