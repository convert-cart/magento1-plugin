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

    /**
     * Get initialization script for ConvertCart
     *
     * @return string|bool Returns script block or false if client key is not set
     */
    public function getInitScript()
    {
        $helper = Mage::helper('convertcart/analytics_cc');
        $clientKey = $helper->getClientKey();
        
        if (empty($clientKey)) {
            return false;
        }

        return Mage::app()->getLayout()
            ->createBlock('core/template')
            ->setClientKey($clientKey)
            ->setScriptDomain($helper->getScriptDomain())
            ->setTemplate('convertcart/init.phtml');
    }

    /**
     * Get stored ConvertCart events from session
     *
     * @return array|bool Returns array of events or false if none found
     */
    public function getCcData()
    {
        $session = $this->_getSession();
        $eventData = $session->getCc_Events();

        return !empty($eventData) ? $eventData : false;
    }

    /**
     * Store Convertcart events in session with enhanced queuing
     *
     * This method stores events in the session with additional validation,
     * deduplication, and rate limiting.
     *
     * @param string $eventName The event name to be mapped to ConvertCart event type
     * @param array $eventData The event data to be stored
     * @param array $options Additional options for event storage:
     *                       - dedupe_key: Key to use for deduplication
     *                       - max_queue_size: Maximum number of events to store (default: 20)
     *                       - ttl: Time to live in seconds (default: 86400 - 24 hours)
     * @return bool True if event was stored successfully, false otherwise
     */
    public function storeCcEvents($eventName, $eventData = array(), $options = array())
    {
        try {
            // Check if tracking is enabled
            $helper = Mage::helper('convertcart/analytics_cc');
            if (!$helper->isEnabled()) {
                return false;
            }

            // Validate event data
            if (!is_array($eventData)) {
                $eventData = array();
            }

            // Apply default options
            $options = array_merge(array(
                'dedupe_key' => null,
                'max_queue_size' => 20,
                'ttl' => 86400, // 24 hours
                'priority' => 'normal' // 'low', 'normal', 'high'
            ), $options);

            $session = $this->_getSession();
            $ccEvents = $session->getCcEvents();
            
            if (empty($ccEvents) || !is_array($ccEvents)) {
                $ccEvents = array();
            }

            // Add event metadata
            $eventData['event_name'] = $eventName;
            $eventData['event_type'] = $helper->getEventType($eventName);
            $eventData = $this->addMetaData($eventData);
            
            // Add event options
            $eventData['_options'] = array(
                'stored_at' => time(),
                'priority' => $options['priority'],
                'ttl' => $options['ttl']
            );

            // Check for duplicate events if dedupe_key is provided
            if ($options['dedupe_key']) {
                $dedupeKey = $options['dedupe_key'];
                foreach ($ccEvents as $key => $existingEvent) {
                    if (isset($existingEvent['_dedupe_key']) && $existingEvent['_dedupe_key'] === $dedupeKey) {
                        // Update existing event instead of adding a new one
                        $ccEvents[$key] = $eventData;
                        $session->setCcEvents($ccEvents);
                        return true;
                    }
                }
                // Add dedupe key to new event
                $eventData['_dedupe_key'] = $dedupeKey;
            }
            
            // Add event to the beginning of the queue (FIFO)
            array_unshift($ccEvents, $eventData);
            
            // Remove expired events
            $now = time();
            $ccEvents = array_filter($ccEvents, function ($event) use ($now) {
                return !isset($event['_options']['stored_at']) ||
                       !isset($event['_options']['ttl']) ||
                       ($now - $event['_options']['stored_at']) < $event['_options']['ttl'];
            });
            
            // Limit queue size
            $ccEvents = array_slice($ccEvents, 0, $options['max_queue_size']);
            
            // Store updated events
            $session->setCcEvents($ccEvents);
            
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }
    
    /**
     * Fetch and clear stored Convertcart events from session
     * Enhanced to handle prioritization, filtering, and batching
     *
     * @param array $options Additional options for event retrieval:
     *                      - limit: Maximum number of events to return (default: 10)
     *                      - priority: Filter by priority ('low', 'normal', 'high' or null for all)
     *                      - clear: Whether to clear events after fetching (default: true)
     * @return array Array of events, sorted by priority and timestamp
     */
    public function fetchCcEvents($options = array())
    {
        try {
            $helper = Mage::helper('convertcart/analytics_cc');
            if (!$helper->isEnabled()) {
                return array();
            }
            
            // Apply default options
            $options = array_merge(array(
                'limit' => 10,
                'priority' => null,
                'clear' => true
            ), $options);
            
            $session = $this->_getSession();
            $events = $session->getCcEvents();
            
            if (empty($events) || !is_array($events)) {
                return array();
            }
            
            // Filter out expired events
            $now = time();
            $validEvents = array_filter($events, function ($event) use ($now) {
                return !isset($event['_options']['stored_at']) ||
                       !isset($event['_options']['ttl']) ||
                       ($now - $event['_options']['stored_at']) < $event['_options']['ttl'];
            });
            
            // Filter by priority if specified
            if ($options['priority']) {
                $priority = strtolower($options['priority']);
                $validEvents = array_filter($validEvents, function ($event) use ($priority) {
                    return isset($event['_options']['priority']) &&
                           strtolower($event['_options']['priority']) === $priority;
                });
            }
            
            // Sort events by priority (high to low) and then by timestamp (oldest first)
            usort($validEvents, function ($a, $b) {
                $priorityOrder = array('high' => 3, 'normal' => 2, 'low' => 1);
                $aPriority = isset($a['_options']['priority']) ? strtolower($a['_options']['priority']) : 'normal';
                $bPriority = isset($b['_options']['priority']) ? strtolower($b['_options']['priority']) : 'normal';
                
                $aScore = isset($priorityOrder[$aPriority]) ? $priorityOrder[$aPriority] : 2;
                $bScore = isset($priorityOrder[$bPriority]) ? $priorityOrder[$bPriority] : 2;
                
                if ($aScore === $bScore) {
                    $aTime = isset($a['_options']['stored_at']) ? $a['_options']['stored_at'] : 0;
                    $bTime = isset($b['_options']['stored_at']) ? $b['_options']['stored_at'] : 0;
                    return $aTime - $bTime; // Oldest first
                }
                
                return $bScore - $aScore; // Higher priority first
            });
            
            // Apply limit
            $result = array_slice($validEvents, 0, $options['limit']);
            
            // Remove fetched events from session if clear is true
            if ($options['clear'] && !empty($result)) {
                $remainingEvents = array();
                $resultIds = array_map(function ($event) {
                    return isset($event['_metadata']['event_id']) ? $event['_metadata']['event_id'] : null;
                }, $result);
                
                // Keep only events that weren't in the result
                foreach ($events as $event) {
                    $eventId = isset($event['_metadata']['event_id']) ? $event['_metadata']['event_id'] : null;
                    if (!$eventId || !in_array($eventId, $resultIds)) {
                        $remainingEvents[] = $event;
                    }
                }
                
                $session->setCcEvents($remainingEvents);
            }
            
            return $result;
        } catch (Exception $e) {
            Mage::logException($e);
            return array();
        }
    }
    
    /**
     * Add metadata to event data
     * Enhanced to include additional contextual information for better tracking
     *
     * @param array $eventData
     * @return array
     */
    protected function addMetaData($eventData)
    {
        if (!is_array($eventData)) {
            $eventData = array();
        }
        
        try {
            $store = Mage::app()->getStore();
            $session = Mage::getSingleton('core/session');
            $customerSession = Mage::getSingleton('customer/session');
            
            // Basic metadata
            $eventData['_metadata'] = array(
                'timestamp' => time(),
                'platform' => 'magento1',
                'plugin_version' => (string)Mage::getConfig()->getNode('modules/Convertcart/version'),
                'tracking_method' => 'server_side',
                'event_id' => md5(uniqid(rand(), true)),
                'session_id' => $session->getSessionId()
            );
            
            // Store information
            if ($store && $store->getId()) {
                $eventData['_store'] = array(
                    'id' => $store->getId(),
                    'code' => $store->getCode(),
                    'name' => $store->getName(),
                    'website_id' => $store->getWebsiteId(),
                    'currency' => $store->getCurrentCurrencyCode(),
                    'base_currency' => $store->getBaseCurrencyCode(),
                    'locale' => Mage::getStoreConfig('general/locale/code', $store->getId())
                );
            }
            
            // Customer information
            $customer = $customerSession->getCustomer();
            if ($customer && $customer->getId()) {
                $eventData['_customer'] = array(
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'group_id' => $customer->getGroupId(),
                    'is_guest' => false,
                    'created_at' => $customer->getCreatedAt(),
                    'store_id' => $customer->getStoreId()
                );
            } else {
                $eventData['_customer'] = array(
                    'is_guest' => true
                );
            }
            
            // Device and browser information
            $httpUserAgent = Mage::helper('core/http')->getHttpUserAgent();
            $eventData['_device'] = array(
                'user_agent' => $httpUserAgent,
                'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
                'is_mobile' => (bool)Zend_Http_UserAgent_Mobile::match($httpUserAgent, $_SERVER)
            );
            
            // Page information if available
            $request = Mage::app()->getRequest();
            if ($request) {
                $eventData['_page'] = array(
                    'url' => Mage::helper('core/url')->getCurrentUrl(),
                    'path' => $request->getPathInfo(),
                    'referrer' => $request->getServer('HTTP_REFERER'),
                    'search_query' => $request->getParam('q')
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
            
            // Ensure we have at least basic metadata even if something fails
            if (!isset($eventData['_metadata'])) {
                $eventData['_metadata'] = array(
                    'timestamp' => time(),
                    'platform' => 'magento1',
                    'error' => 'metadata_processing_error'
                );
            }
        }
        
        return $eventData;
    }
    
    public function insertMeta($includeCustomerInfo = 0)
    {
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false) {
            return;
        }

        $metaData = array();
        $metaData['date'] = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
        if ($includeCustomerInfo !=0) {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $metaData['customer_status'] = 'logged_in';
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if (!is_object($customer)) {
                    return $metaData;
                }
                $metaData['customer_email'] = $customer->getEmail();
            } else {
                $metaData['customer_status'] = 'guest';
            }

            $store = Mage::app()->getStore();

            if (!is_object($store)) {
                return $metaData;
            }

            $metaData['current_currency'] = $store->getCurrentCurrencyCode();
            $metaData['base_currency'] = $store->getBaseCurrencyCode();
            $metaData['current_currency_rate'] = $store->getCurrentCurrencyRate();

            $locale = Mage::app()->getLocale();
            if (!is_object($locale)) {
                $metaData['language'] = $locale->getLocaleCode();
            }

            $metaData['store_code'] = $store->getCode();
            $metaData['store_id'] = $store->getId();

            $website = Mage::app()->getWebsite();

            if (!is_object($website)) {
                return $metaData;
            }

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
                if (is_object($store)) {
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                }
                    $imageUrl = $this->getImageUrl($imagePath);
                if ($imageUrl != null) {
                    $cartItem['image'] = $imageUrl;
                }
            }

            $cart[] = $cartItem;
        }

        return $cart;
    }


    /**
     * Get product options from cart item
     *
     * @param mixed $item
     * @return array|null
     */
    public function getCartItemOptions($item)
    {
        if (!$this->isValidItem($item)) {
            return null;
        }

        $product = $item->getProduct();
        $productOptions = $this->getProductOrderOptions($product);
        
        if (empty($productOptions['options'])) {
            return null;
        }

        return $this->formatCustomOptions($productOptions['options']);
    }

    /**
     * Check if item is valid
     *
     * @param mixed $item
     * @return bool
     */
    protected function isValidItem($item)
    {
        if (!is_object($item)) {
            return false;
        }

        $product = $item->getProduct();
        if (!is_object($product)) {
            return false;
        }

        $productInstance = $product->getTypeInstance(true);
        return is_object($productInstance);
    }

    /**
     * Get order options from product
     *
     * @param mixed $product
     * @return array
     */
    protected function getProductOrderOptions($product)
    {
        if (!is_object($product)) {
            return array('options' => null);
        }

        $productInstance = $product->getTypeInstance(true);
        if (!is_object($productInstance)) {
            return array('options' => null);
        }

        $options = $productInstance->getOrderOptions($product);
        return is_array($options) ? $options : array('options' => null);
    }

    /**
     * Format custom options array
     *
     * @param array $options
     * @return array
     */
    protected function formatCustomOptions(array $options)
    {
        $customOptions = array();
        
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            
            $customOptions[] = array(
                'label' => isset($option['label']) ? $option['label'] : null,
                'value' => isset($option['value']) ? $option['value'] : null,
                'option_id' => isset($option['option_id']) ? $option['option_id'] : null,
                'option_type' => isset($option['option_type']) ? $option['option_type'] : null
            );
        }

        return $customOptions;
    }

    public function getOrderItemOptions($item)
    {
        if (!is_object($item)) {
            return null;
        }

        $options = $item->getProductOptions();
        if (!isset($options['options']) || empty($options['options'])) {
            return null;
        }

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
            $wlist = array();
            $wlist['sku'] = str_replace("'", "", $wishlistItem->getSku());
            $resource = Mage::getSingleton('catalog/product');
            if (is_object($resource)) {
                $resource = $resource->getResource();
                if (is_object($store) and is_object($resource)) {
                    $wlist['url_key'] = $resource->getAttributeRawValue(
                        $wishlistItem->getProductId(),
                        "url_key",
                        $store
                    );
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
                $wlist = array();
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
        if (!isset($id)) {
            return $wishlist;
        }
        $amModel = Mage::getModel('amlist/item');
        if (!is_object($amModel)) {
            return $wishlist;
        }
        $list = $amModel->getCollection()->addFieldToFilter('list_id', $id)->setPageSize($wItemlimit);
        $store = Mage::app()->getStore();
        foreach ($list as $listItem) {
            $wItem = array();
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

    /**
     * Get formatted order items for tracking
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOrderItems($order)
    {
        $items = array();
        if (!is_object($order)) {
            return $items;
        }

        $store = $order->getStore();
        $currency = is_object($store) ? $store->getCurrentCurrencyCode() : null;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $orderItem = array();
            $orderItem['name'] = str_replace("'", "", $item->getName());
            $orderItem['price'] = $this->getPrice($item->getPrice());
            $orderItem['original_price'] = $this->getPrice($item->getOriginalPrice());
            $orderItem['currency'] = $currency;
            $orderItem['quantity'] = (int)$item->getQtyOrdered();
            $orderItem['id'] = $item->getProductId();
            $orderItem['sku'] = $item->getSku();
            $orderItem['row_total'] = $this->getPrice($item->getRowTotal());
            $orderItem['tax_amount'] = $this->getPrice($item->getTaxAmount());
            $orderItem['discount_amount'] = abs($this->getPrice($item->getDiscountAmount()));
            
            // Get product URL
            $product = $item->getProduct();
            if (is_object($product)) {
                $orderItem['url'] = $product->getProductUrl();
                
                // Get product image
                $resource = $product->getResource();
                if (is_object($resource) && is_object($store)) {
                    $imagePath = $resource->getAttributeRawValue($product->getId(), 'image', $store);
                    $imageUrl = $this->getImageUrl($imagePath);
                    if ($imageUrl) {
                        $orderItem['image'] = $imageUrl;
                    }
                }
            }
            
            // Get product options
            $options = $this->getOrderItemOptions($item);
            if (!empty($options)) {
                $orderItem['options'] = $options;
            }
            
            // Get parent product ID for configurable products
            if ($item->getProductType() == 'configurable') {
                $children = $item->getChildrenItems();
                if (count($children) > 0) {
                    foreach ($children as $child) {
                        $orderItem['variant_id'] = $child->getProductId();
                        $orderItem['variant_sku'] = $child->getSku();
                        break; // Only take the first child
                    }
                }
            }
            
            $items[] = $orderItem;
        }
        
        return $items;
    }
    
    /**
     * Get Amasty Favorites
     *
     * @return array
     */
    public function getAmastyFavorites()
    {
        $ccHelper = Mage::Helper('convertcart/analytics_cc');
        $amFavourite = array();
        $amModel = Mage::getModel('amlist/list');
        $customerSessionModel = Mage::getSingleton('customer/session');
        if (!is_object($amModel)) {
            return $amFavourite;
        }
        if (!$customerSessionModel->isLoggedIn()) {
            return $amFavourite;
        }
        $customerID = $customerSessionModel->getId();
        $lists = $amModel->getCollection()->addFieldToFilter('customer_id', $customerID);
        $amFavourite = array();
        $amFavourite['items'] = array();

        foreach ($lists as $list) {
            $item = array();
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
            if ($magentoVersion[1]>4) {
                return;
            }
        }

        //if customer logged in, then created successfully
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        $ccData = array();
        $ccData['event_type'] = Mage::Helper('convertcart_model_analytics/cc')->getEventType("customerRegister");
        $ccData['event_data'] = $this->getCustomerData($customer);
        $ccData['meta_data'] = Mage::getModel('convertcart_model_analytics/cc')->insertMeta();

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
        if (Mage::Helper('convertcart/analytics_cc')->isEnabled() == false) {
            return;
        }

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
        if (!isset($price)) {
            return 0;
        }

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
