<?php
class Convertcart_Analytics_Model_Observer
{
    public function addBlock()
    {
        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $ccData = $cc->getCcData();

        //checking if anything to include
        if ($ccData==false) {
            return;
        }

        $layout = Mage::getSingleton('core/layout');
        if (!$layout) {
            return;
        }
        
        $beforeBodyEnd = $layout->getBlock('before_body_end');
        if (!$beforeBodyEnd) {
            return;
        }


        $cc->clearData();
        foreach ($ccData as $singleEvent) {
            $singleEventData = $singleEvent['event_data'];
            $singleEventData['ccEvent'] = $singleEvent['event_type'];
            $singleEventData['meta_data'] = $singleEvent['meta_data'];

            $eventData = Mage::app()->getLayout()->createBlock('core/template')
                        ->addData(
                            array(
                                'cache_lifetime'=> null,
                                'cache_tags' => array(
                                    Mage_Core_Model_Store::CACHE_TAG,
                                    Mage_Cms_Model_Block::CACHE_TAG,
                                    'ccBlock'
                                ),
                                'cache_key' => 'ccEvent',
                            )
                        ); 

            $eventData = $eventData->setEventData(json_encode($singleEventData))
                                   ->setTemplate('convertcart/event.phtml');
            $layout->getBlock('before_body_end')->append($eventData);
        }//foreach singles_event
    }//addBlock function ends

    public function ccInit()
    {
        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $initData = $cc->getInitScript();
        if($initData == false) //checking if we can include script
            return;

        $layout = Mage::getSingleton('core/layout');
        if (!$layout) {
            return;
        }
        
        $head = $layout->getBlock('head');
        if (!$head) {
            return;
        }
        $layout->getBlock('head')->append($initData);
    }//ccInit function ends


    public function homepageView($observer)
    {
        $action = $observer->getAction();
        if (!$action) { 
            return; 
        }
             
        if (!in_array($action->getFullActionName(), array('cms_index_index'))) {
            return;
        }

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("homepageView");
        $ccView['event_data'] = '';
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);

    } // homepageView function ends

    public function cmsView($observer)
    {
        $action = $observer->getAction();
        if (!$action) { 
            return; 
        }
              
        if (!in_array($action->getFullActionName(), array('cms_page_view'))) {
            return;
        }

        $cmsInfo = Mage::getSingleton('cms/page');

        if ($cmsInfo) {
            $ccView['event_data']['title'] = $cmsInfo->getTitle();
            $ccView['event_data']['url_slug'] = $cmsInfo->getIdentifier();        
        }

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cmsView");
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);

    } // cmsView function ends


    public function productView($observer)
    {
        $action = $observer->getAction();
        if (!$action) { 
            return; 
        }
        
        $request = $action->getRequest();
        if (!$request) { 
            return; 
        }
        
        $params = $request->getParams();
        
        if (!in_array($action->getFullActionName(), array('catalog_product_view'))) {
            return;
        }

        $product = Mage::registry('current_product');

        if(!is_object($product))
            return;

        $summaryData = Mage::getModel('review/review_summary')->load($product->getId());

        if(is_object($summaryData))
            $ratingPercent = $summaryData->getRatingSummary();

        if($ratingPercent > 0)
            $rating = ($ratingPercent/100)*5;
        else
            $rating = 0;

        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();

        $cc = Mage::getModel('convertcart_analytics/cc');
        $productData = array(
            'id' => $product->getId(),
            'url' => $product->getProductUrl(),
            'name' => $product->getName(),
            'price' => $cc->getPrice($product->getPrice()),
            'final_price' => $cc->getPrice($product->getFinalPrice()), 
            'currency' => $currency,
            'short_description' => strip_tags($product->getShortDescription()),
            'sku' => $product->getSku(),
            'rating' => $rating
        );

        if($product->getImage() != null and $product->getImage() != "no_selection")
            $productData['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (is_object($stock)) {
            $productData['is_in_stock'] = $stock->getIsInStock();
        }

        $categories = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('entity_id', array('in'=>$product->getCategoryIds()));

        $productData['category'] = array();
        //$productData['category_ids'] = $product->getCategoryIds();
        $c=0;
        foreach ($categories as $category) {
            $productData['category'][$c]['name'] = $category->getName();
            $productData['category'][$c]['id'] = $category->getId();            
            $c++;
        }

        $productData['type'] = $product->getTypeId();

        if ($productData['type'] == "configurable") {
            $productData['product_type'] = "parent";
            $childProducts = Mage::getModel('catalog/product_type_configurable')
                                ->getChildrenIds($product->getId());            
            $productData['child_ids'] = $childProducts[0];
        }
        else
            $productData['product_type'] = "simple";

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("productView");
        $ccView['event_data'] = $productData;
        $ccView['event_data']['params'] = $params;    
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);
    }//productView function ends

    public function categoryView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return; 
        }
        
        $request = $action->getRequest();
        if (!$request) {
            return; 
        }
        
        $params = $request->getParams();

        if (!in_array($action->getFullActionName(), array('catalog_category_view'))) {
            return;
        }

        $toolbar = Mage::getBlockSingleton('catalog/product_list_toolbar');
        $category = Mage::getSingleton('catalog/layer')->getCurrentCategory();

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("categoryView");

        $ccView['event_data']['params'] = $params;

        if ($toolbar) {
            $ccView['event_data']['sort_by'] = $toolbar->getCurrentOrder()." - ".$toolbar->getCurrentDirection();
            $ccView['event_data']['current_mode'] = $toolbar->getCurrentMode();
        }//if toolbar ends

        if ($category) {
            $ccView['event_data']['name'] = $category->getName();
            $ccView['event_data']['id'] = $category->getId();
            $ccView['event_data']['url'] = $category->getUrl();            
        }
        elseif(isset($params['id']))
            $ccView['event_data']['id'] = $params['id'];   

        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccView);
    }//categoryView function ends


    public function searchView($observer)
    {  
        $request = Mage::app()->getRequest();

        if ($request) {
            $params = $request->getParams();
            if ($request->isXmlHttpRequest()) //ajax requests, ignore
                return;
        }

        $query = $observer->getDataObject();
        if ($query) {
            $ccView['event_data']['query'] = $query->getQueryText();
            $ccView['event_data']['items_count'] = $query->getNumResults();
        }

        //in some themes / modules above approach doesnt work..

        if (!$ccView['event_data']['query'] and $params) {
            $ccView['event_data']['query'] = $params['q'];
        }
        $ccView['event_data']['params'] = $params;

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("searchView");
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);
    }//searchView function ends

    public function cartView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return; 
        }
            
        if (!in_array($action->getFullActionName(), array('checkout_cart_index'))) {
            return;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote) {
            return;
        }

        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();
        $cc = Mage::getModel('convertcart_analytics/cc');

        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $cc->getPrice($item->getPrice());
            $cartItem['currency'] = $currency;
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();
            $cartItem['customOptions'] = Mage::getSingleton('convertcart_analytics/cc')->getCartItemOptions($item);

            $product = $item->getProduct();
            if (is_object($product)) {
                $cartItem['url'] = $product->getProductUrl();
            }
            $resource = Mage::getSingleton('catalog/product')->getResource();

            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if(is_object($store))
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                if($imagePath != null and $imagePath != "no_selection")
                    $cartItem['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
            }
            $cart[] = $cartItem;
        }

        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cartView");
        $ccView['event_data']['items'] = $cart;
        $ccView['event_data']['currency'] = $currency;
        $ccView['event_data']['coupon_code'] = $quote->getCouponCode();

        $ccView['event_data']['subtotal'] = $cc->getValue($quote->getSubtotal());
        $ccView['event_data']['total'] = $cc->getValue($quote->getGrandTotal());
        $ccView['event_data']['base_total'] = $cc->getValue($quote->getBaseGrandTotal());
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta(1);

        $cc->storeData($ccView);
    } // cartView function ends

    public function checkoutView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return;
        }

        $checkoutActionNames = array('onepagecheckout_index_index', 'checkout_onepage_index', 'onestepcheckout_index_index');
        if (!in_array($action->getFullActionName(), $checkoutActionNames)) {
            return;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote) {
            return;
        }

        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();
        $cc = Mage::getModel('convertcart_analytics/cc');

        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $cc->getPrice($item->getPrice());
            $cartItem['currency'] = $currency;
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();
            $cartItem['customOptions'] = Mage::getSingleton('convertcart_analytics/cc')->getCartItemOptions($item);

            $product = $item->getProduct();
            if (is_object($product)) {
                $cartItem['url'] = $product->getProductUrl();
            }
            $resource = Mage::getSingleton('catalog/product')->getResource();

            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if(is_object($store))
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                if($imagePath != null and $imagePath != "no_selection")
                    $cartItem['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
            }
            $cart[] = $cartItem;
        }

        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("checkoutView");
        $ccView['event_data']['items'] = $cart;
        $ccView['event_data']['currency'] = $currency;
        $ccView['event_data']['coupon_code'] = $quote->getCouponCode();

        $ccView['event_data']['subtotal'] = $cc->getValue($quote->getSubtotal());
        $ccView['event_data']['total'] = $cc->getValue($quote->getGrandTotal());
        $ccView['event_data']['base_total'] = $cc->getValue($quote->getBaseGrandTotal());
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta(1);

        $cc->storeData($ccView);
    } // checkoutView function ends    

    public function loggedIn($observer)
    {
        $customer = $observer->getCustomer();
        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();
        $customerData['created_at'] = $customer->getCreatedAt();
        $customerTotals = Mage::getResourceModel('sales/sale_collection')
             ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
             ->setCustomerFilter($customer)
             ->load()
             ->getTotals();

        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();
        $customerData['currency'] =  $currency;
        $customerData['num_orders'] =  $customerTotals->getNumOrders();

        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $customerData['lifetime_sales'] =  $cc->getValue($customerTotals->getLifetime());

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedIn");
        $ccData['event_data'] = $customerData;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc->storeData($ccData);
    }//loggedIn function ends

    public function loggedOut($observer)
    {
        $customer = $observer->getCustomer();
        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedOut");
        $ccData['event_data'] = $customerData;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//loggedOut function ends

    public function customerRegister($observer)
    {
        $customer = $observer->getCustomer();
        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("customerRegister");
        $ccData['event_data'] = $customerData;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//customerRegister function ends

    public function customerRegisterOld($observer) //addedto support magento 1.4
    {
        Mage::getSingleton('convertcart_analytics/cc')->customerRegisterOld();
    }//customerRegisterOld function ends

    public function customerRegisterCheckOld($observer) //addedto support magento 1.4
    {
        if ($observer->getQuote()->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)
            return;
        Mage::getSingleton('convertcart_analytics/cc')->customerRegisterOld();
    }

    public function addToCart($observer)
    {
        $product = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();
        $store = Mage::app()->getStore();

        if (is_object($store))
            $currency = $store->getCurrentCurrencyCode();
        if (is_object($quoteItem)) {
            $cart['quantity'] = $quoteItem->getQty();
            $cart['customOptions'] = Mage::getSingleton('convertcart_analytics/cc')->getCartItemOptions($quoteItem);
        }
        if (is_object($product)) {
            $cc = Mage::getModel('convertcart_analytics/cc');
            $cart['name'] = str_replace("'", "", $product->getName());
            $cart['price'] = $cc->getPrice($product->getFinalPrice());
            $cart['currency'] = $currency;
            $cart['id'] = $product->getId();
            $cart['sku'] = $product->getSku();
            $cart['url'] = $product->getProductUrl();
            $imagePath = $product->getImage();
            if($imagePath != null and $imagePath != "no_selection")
                $cart['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
        }
        
        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addToCart");
        $ccData['event_data'] = $cart;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//addToCart function ends


    public function removeFromCart($observer)
    {
        $quoteItem = $observer->getQuoteItem();
        if (!is_object($quoteItem))
            return;

        $product = $quoteItem->getProduct();
        if (!is_object($product))
            return;

        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();

        $cc = Mage::getModel('convertcart_analytics/cc');
        $cart['name'] = str_replace("'", "", $product->getName());
        $cart['price'] = $cc->getPrice($product->getFinalPrice());
        $cart['currency'] = $currency;
        $cart['quantity'] = $quoteItem->getQty();
        $cart['id'] = $product->getId();
        $cart['sku'] = $product->getSku();      
        $cart['url'] = $product->getProductUrl();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCart");
        $ccData['event_data'] = $cart;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//removeFromCart function ends

    public function ordered($observer)
    {
        $orderId = $observer->getData('order_ids');
        if ($orderId)
            $order = Mage::getModel('sales/order')->load($orderId); 

        if(!is_object($order))
            return;

        // $order = $observer->getOrder();
        $store = Mage::app()->getStore();
        if(is_object($store))
            $currency = $store->getCurrentCurrencyCode();

        foreach ($order->getAllVisibleItems() as $item) {
            $orderItem['name'] = str_replace("'", "", $item->getName());
            $orderItem['price'] = $item->getPrice();
            $orderItem['currency'] = $currency;            
            $orderItem['quantity'] = $item->getQtyOrdered();
            $orderItem['id'] = $item->getProductId();
            $orderItem['sku'] = $item->getSku();
            $orderItem['customOptions'] = Mage::getSingleton('convertcart_analytics/cc')->getOrderItemOptions($item);

            $product = $item->getProduct();
            if (is_object($product)) {
                $orderItem['url'] = $product->getProductUrl();
            }
            $resource = Mage::getSingleton('catalog/product')->getResource();

            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if(is_object($store))
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                if($imagePath != null and $imagePath != "no_selection")
                    $orderItem['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
            }
            $orderItems[] = $orderItem;
        }

        if ($order->getCustomerId()) {
            $customerOrders = Mage::getResourceModel('sales/order_collection')
                            ->addFieldToSelect('customer_id')
                            ->addFieldToFilter('customer_id', $order->getCustomerId())
                            ->addFieldToFilter('state', array('nin' => array('canceled','pending')));

            if (is_object($customerOrders))
                $orderCount = $customerOrders->getSize() ? $customerOrders->getSize() : 1;
        } else
            $orderCount = 1;

        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("ordered");
        $ccData['event_data']['orderId'] = $order->getIncrementId();
        $ccData['event_data']['order_email'] = $order->getCustomerEmail();
        $ccData['event_data']['is_guest'] = $order->getCustomerIsGuest();
        $ccData['event_data']['order_count'] = $orderCount;
        $ccData['event_data']['items'] = $orderItems;
        $ccData['event_data']['coupon_code'] = $order->getCouponCode();
        $ccData['event_data']['shipping_method'] = $order->getShippingDescription();
        $ccData['event_data']['payment_method'] = $order->getPayment()->getMethod(); 
        $ccData['event_data']['status'] = $order->getStatus();
        $ccData['event_data']['currency'] = $currency;

        $ccData['event_data']['shipping_amount'] = $cc->getValue($order->getShippingAmount());
        $ccData['event_data']['tax_amount'] = $cc->getValue($order->getTaxAmount());
        $ccData['event_data']['discount_amount'] = $cc->getValue($order->getDiscountAmount());
        $ccView['event_data']['subtotal'] = $cc->getValue($order->getSubtotal());
        $ccData['event_data']['total'] = $cc->getValue($order->getGrandTotal());
        $ccData['event_data']['base_total'] = $cc->getValue($order->getBaseGrandTotal());
        $ccData['event_data']['total_due'] = $cc->getValue($order->getTotalDue());
        $ccData['event_data']['base_total_due'] = $cc->getValue($order->getBaseTotalDue());

        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta(1);
        $cc->storeData($ccData);
    }//ordered function ends

    public function addToWishlist($observer)
    {
        $product  = $observer->getProduct();

        if(!is_object($product))
            return;

        $wishlist['name'] = str_replace("'", "", $product->getName());
        $wishlist['id'] = $product->getId();
        $wishlist['sku'] = $product->getSku();      
        $wishlist['url'] = $product->getProductUrl();

        $imagePath = $product->getImage();
        if($imagePath != null and $imagePath != "no_selection")
            $wishlist['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addToWishlist");
        $ccData['event_data'] = $wishlist;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//addToWishlist function ends

    public function wishlistView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return; 
        }
        
        $request = $action->getRequest();
        if (!$request) { 
            return; 
        }
        
        $params = $request->getParams();        
        if (!in_array($action->getFullActionName(), array('wishlist_index_index'))) {
            return;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistView");
        $ccData['event_data']['items'] = Mage::getSingleton('convertcart_analytics/cc')->getWishlistItems();
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//wishlistView function ends

    public function removeFromWishlist($observer)
    {
        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistUpdated");
        $ccData['event_data']['items'] = Mage::getSingleton('convertcart_analytics/cc')->getWishlistItems();
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//removeFromWishlist function ends

    public function addToCompare($observer)
    {
        $product  = $observer->getProduct();

        $compare['name'] = str_replace("'", "", $product->getName());
        $compare['id'] = $product->getId();
        $compare['sku'] = $product->getSku();      
        $compare['url'] = $product->getProductUrl();

        $imagePath = $product->getImage();
        if($imagePath != null and $imagePath != "no_selection")
            $compare['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addToCompare");
        $ccData['event_data'] = $compare;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//addToCompare function ends

    public function removeFromCompare($observer)
    {
        $product  = $observer->getProduct();
        $compare['id'] = $product->getProductId();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCompare");
        $ccData['event_data'] = $compare;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//removeFromCompare function ends


    public function compareView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return; 
        }
        
        if (!in_array($action->getFullActionName(), array('catalog_product_compare_index'))) {
            return;
        }

        $compareCollection = Mage::helper('catalog/product_compare')->getItemCollection();
        $compareItems = array();
        $store = Mage::app()->getStore();

        foreach ($compareCollection as $product) {
            $compareItem = array();
            $compareItem['sku'] = $product->getSku();
            $compareItem['id'] = $product->getId();
            $compareItem['name'] = $product->getName();            
            $compareItem['sku'] = $product->getSku();      
            $compareItem['url'] = $product->getProductUrl();

            $resource = Mage::getSingleton('catalog/product')->getResource();
            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if(is_object($store))
                    $imagePath = $resource->getAttributeRawValue($product->getId(), "image", $store);
                if($imagePath != null and $imagePath != "no_selection")
                    $compareItem['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
            }

            $compareItems[] = $compareItem;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("compareView");
        $ccData['event_data']['items'] = $compareItems;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//compareView ends

    public function couponInfo($observer)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $couponcode = $quote->getData('coupon_code');

        $request = Mage::app()->getRequest();
        if ($request) {
            $params = $request->getParams();
        }

        if($params['remove'] == 1)
            $status = "couponRemoved";
        elseif($couponcode == $params['coupon_code'])
            $status = "couponApplied";
        elseif($couponcode == '' or !$couponcode)
            $status = "couponDenied";        

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType($status);
        $ccData['event_data']['coupon_code'] = $params['coupon_code'];
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//couponInfo function ends

    public function reviewSave($observer)
    {
        $review=$observer->getEvent()->getObject();

        if(!$review)
            return;

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("reviewSave");
        $ccData['event_data']['nickname'] = $review['nickname'];
        $ccData['event_data']['title'] = $review['title'];
        $ccData['event_data']['detail'] = $review['detail'];    
        $ccData['event_data']['review_id'] = $review['review_id'];
        $ccData['event_data']['product_id'] = $review['entity_pk_value'];
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//reviewSave function ends
}