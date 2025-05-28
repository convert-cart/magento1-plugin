<?php
class Convertcart_Model_Analytics_Cc extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('convertcart/analytics_cc_session');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('convertcart/analytics_cc_session');
    }

    public function getInitScript()
    {
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false) //dont proceed if not enabled
            return;

        $scriptDomain = Mage::Helper('convertcart/analytics_cc')->getScriptDomain();
        $clientKey = Mage::Helper('convertcart/analytics_cc')->getClientKey();
        if (!isset($clientKey))
            return ;

        $script = Mage::app()->getLayout()->createBlock('core/template')
                  ->setClientKey($clientKey)
                  ->setScriptDomain($scriptDomain)
                  ->setTemplate('convertcart/init.phtml');
        return $script;
    }

    public function getCcData()
    {
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false) //dont proceed if not enabled
            return;

        $session = $this->_getSession();
        $eventData = $session->getCc_Events();

        if (empty($eventData))
            return;

        return $eventData;
    }

    public function insertMeta($includeCustomerInfo = 0)
    {
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false)
            return;

        $metaData = array();
        $metaData['date'] = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
        if ($includeCustomerInfo !=0) {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $metaData['customer_status'] = 'logged_in';
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if(!is_object($customer))
                    return $metaData;
                $metaData['customer_email'] = $customer->getEmail();
            } else {
                $metaData['customer_status'] = 'guest';
            }

            $store = Mage::app()->getStore();

            if (!is_object($store))
                return $metaData;

            $metaData['current_currency'] = $store->getCurrentCurrencyCode();
            $metaData['base_currency'] = $store->getBaseCurrencyCode();
            $metaData['current_currency_rate'] = $store->getCurrentCurrencyRate();

            $locale = Mage::app()->getLocale();
            if (!is_object($locale))
                $metaData['language'] = $locale->getLocaleCode();

            $metaData['store_code'] = $store->getCode();
            $metaData['store_id'] = $store->getId();

            $website = Mage::app()->getWebsite();

            if (!is_object($website))
                return $metaData;

            $metaData['website_id'] = $website->getId();
            $metaData['website_code'] = $website->getCode();
        }

        // maintianing plugin_version nomenclature across all plugins
        $metaData['plugin_version'] = Mage::Helper('convertcart/analytics_cc')->getModuleVersion();
        return $metaData;
    }

    public function getCartItems($quote)
    {
        $cart = array();
        if (!is_object($quote)) {
            return $cart;
        }

        $currency = null;
        $store = Mage::app()->getStore();
        if (is_object($store)) {
            $currency = $store->getCurrentCurrencyCode();
        }

        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $this->getPrice($item->getPrice());
            $cartItem['currency'] = $currency;
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();
            $cartItem['customOptions'] = $this->getCartItemOptions($item);
            $product = $item->getProduct();
            if (is_object($product)) {
                $cartItem['url'] = $product->getProductUrl();
            }

            $resource = Mage::getSingleton('catalog/product')->getResource();
            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if (is_object($store))
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                    $imageUrl = $this->getImageUrl($imagePath);
                    if ($imageUrl != null) {
                        $cartItem['image'] = $imageUrl;
                    }
            }

            $cart[] = $cartItem;
        }

        return $cart;
    }


    public function getCartItemOptions($item)
    {
        if (!is_object($item))
            return null;

        $product = $item->getProduct();
        if (!is_object($product))
            return null;

        $productInstance = $product->getTypeInstance(true);
        if (!is_object($productInstance))
            return null;

        $productOptions = $productInstance->getOrderOptions($product);
        $options = isset($productOptions['options']) ? $productOptions['options'] : null;
        if (!isset($options) || empty($options))
            return null;

        $customOptions = array();
        foreach ($options as $option) {
            $customOption = array();
            $customOption['label'] = isset($option['label']) ? $option['label'] : null;
            $customOption['value'] = isset($option['value']) ? $option['value'] : null;
            $customOption['option_id'] = isset($option['option_id']) ? $option['option_id'] : null;
            $customOption['option_type'] = isset($option['option_type']) ? $option['option_type'] : null;
            $customOptions[] = $customOption;
        }

        return $customOptions;
    }

    public function getOrderItemOptions($item)
    {
        if (!is_object($item))
            return null;

        $options = $item->getProductOptions();
        if (!isset($options['options']) || empty($options['options']))
            return null;

        $options = $options['options'];
        $customOptions = array();
        foreach ($options as $option) {
            $customOption = array();
            $customOption['label'] = isset($option['label']) ? $option['label'] : null;
            $customOption['value'] = isset($option['value']) ? $option['value'] : null;
            $customOption['option_id'] = isset($option['option_id']) ? $option['option_id'] : null;
            $customOption['option_type'] = isset($option['option_type']) ? $option['option_type'] : null;
            $customOptions[] = $customOption;
        }

        return $customOptions;
    }

    public function getOldWishlistItems()
    {
        // wishlist items for magento < v1.4
        $wishlist = array();
        $store = Mage::app()->getStore();
        $wishlistItems = Mage::helper('wishlist')->getItemCollection();
        foreach ($wishlistItems as $wishlistItem) {
            $wlist['sku'] = str_replace("'", "", $wishlistItem->getSku());
            $resource = Mage::getSingleton('catalog/product');
            if (is_object($resource)) {
                $resource = $resource->getResource();
                if (is_object($store) and is_object($resource)) {
                    $wlist['url_key'] = $resource->getAttributeRawValue($wishlistItem->getProductId(), "url_key", $store);
                    $imagePath = $resource->getAttributeRawValue($wishlistItem->getProductId(), "image", $store);
                    $imageUrl = $this->getImageUrl($imagePath);
                    if ($imageUrl != null) {
                        $wlist['image']= $imageUrl;
                    }
                }
            }

            $wishlist[] = $wlist;
        }

        return $wishlist;
    }

    public function getWishlistItems()
    {
        $wishlist = array();
        $magentoVersion = Mage::getVersion();
        if (!$magentoVersion) {
            return $wishlist;
        }

        $magentoVersion = explode(".", $magentoVersion);
        if ($magentoVersion[1]<=4) {
            $wishlist = $this->getOldWishlistItems();
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
                        $wlist['sku'] = $resource->getAttributeRawValue($product->getId(), "sku", $store);
                        $imagePath = $resource->getAttributeRawValue($product->getId(), "image", $store);
                        $imageUrl = $this->getImageUrl($imagePath);
                        if ($imageUrl != null) {
                            $wlist['image']= $imageUrl;
                        }
                    }
                }

                $wishlist[] = $wlist;
            }
        } //else magento >= 1.5

        return $wishlist;
    }

    public function getAmastyWishlistItems()
    {
        $wItemlimit = 10;
        $ccHelper = Mage::Helper('convertcart/analytics_cc');
        $id = $ccHelper->sanitizeParam(Mage::app()->getRequest()->getParam('id'));
        $wishlist = array();
        if(!isset($id)) return $wishlist;
        $amModel = Mage::getModel('amlist/item');
        if (!is_object($amModel)) return $wishlist;
        $list = $amModel->getCollection()->addFieldToFilter('list_id', $id)->setPageSize($wItemlimit);
        $store = Mage::app()->getStore();
        foreach ($list as $listItem) {
            $wItem['id'] = $ccHelper->getArrValue($listItem, 'item_id');
            $wItem['quantity'] = $ccHelper->getArrValue($listItem, 'qty');
            $productId = $listItem->getProductId();
            $resource = Mage::getSingleton('catalog/product')->getResource();
            if (is_object($resource)) {
                $wItem['name'] = str_replace("'", "", $resource->getAttributeRawValue($productId, "name", $store));
                $urlKey = $resource->getAttributeRawValue($productId, 'url_path', $store);
                if ($urlKey != null) {
                    $wItem['url']  = Mage::getBaseUrl() . $urlKey;
                }

                $wItem['sku'] = $resource->getAttributeRawValue($productId, "sku", $store);
                $imagePath = $resource->getAttributeRawValue($productId, "image", $store);
                $imageUrl = $this->getImageUrl($imagePath);
                if ($imageUrl != null) {
                    $wItem['image']= $imageUrl;
                }

                $wishlist[] = $wItem;
            }
        }

       return $wishlist;
    }

    public function getAmastyFavorites()
    {
        $ccHelper = Mage::Helper('convertcart/analytics_cc');
        $amFavourite = array();
        $amModel = Mage::getModel('amlist/list');
        $customerSessionModel = Mage::getSingleton('customer/session');
        if (!is_object($amModel)) return $amFavourite;
        if (!$customerSessionModel->isLoggedIn()) return $amFavourite;
        $customerID = $customerSessionModel->getId();
        $lists = $amModel->getCollection()->addFieldToFilter('customer_id', $customerID);
        $amFavourite['items'] = array();

        foreach ($lists as $list) {
            $customerId = $ccHelper->getArrValue($list, 'customer_id');
            $item['listId'] = $ccHelper->getArrValue($list, 'list_id');
            $item['title']  = $ccHelper->getArrValue($list, 'title');
            $item['isDefault'] = $ccHelper->getArrValue($list, 'is_default');
            $amFavourite['items'][] = $item;
        }

        $amFavourite['customerId'] = $customerId;
        return $amFavourite;
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

        //if customer logged in, then created successfully
        if (!Mage::getSingleton('customer/session')->isLoggedIn())
            return;

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        $ccData['event_type'] = Mage::Helper('convertcart_model_analytics/cc')->getEventType("customerRegister");
        $ccData['event_data'] = $this->getCustomerData($customer);
        $ccData['meta_data'] =  Mage::getModel('convertcart_model_analytics/cc')->insertMeta();

        $cc = Mage::getModel('convertcart_model_analytics/cc');
        $cc->storeData($ccData);
    }

    public function getCustomerData($customer)
    {
        $customerData = array();
        if (!is_object($customer)) {
            return $customerData;
        }

        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();

        return $customerData;
    }

    public function storeData($eventData)
    {
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false)
            return;

        $session = $this->_getSession();
        $ccData = $session->getCc_Events();

        if (!$ccData or empty($ccData)) {
            $ccData = array();
            $ccData[] = $eventData;
        } else {
            $ccData[] = $eventData;
        }

        $session->setCc_Events($ccData);
        return $this;
    }

    public function clearData()
    {
        Mage::getSingleton('convertcart/analytics_cc_session')
        ->setCc_Events(array());
        return $this;
    }

    public function getPrice($price)
    {
        if (!isset($price))
            return 0;

        return Mage::helper('core')->currency($price, false, false);
    }

    public function getImageUrl($imagePath)
    {
        $imageUrl = null;
        if ($imagePath != null and $imagePath != "no_selection") {
            $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
        }

        return $imageUrl;
    }

    public function getValue($number)
    {
        if ($number == null or !isset($number)) {
            return 0;
        } else {
            return $number;
        }
    }
}