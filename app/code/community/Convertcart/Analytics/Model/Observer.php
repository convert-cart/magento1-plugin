<?php
class Convertcart_Analytics_Model_Observer
{
    public function generateKey()
    {
        Mage::Helper('convertcart_analytics')->generateKey();
    }

    public function addBlock()
    {
        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $ccData = $cc->getData();

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
            $eventData = Mage::app()->getLayout()->createBlock('core/template')
                              ->setEventType(json_encode($singleEvent['event_type']))
                              ->setEventData(json_encode($singleEvent['event_data']))
                              ->setMetaData(json_encode($singleEvent['meta_data']))
                              ->setTemplate('convertcart/event.phtml');
            $layout->getBlock('before_body_end')->append($eventData);
        }//foreach singles_event
    }//addBlock function ends

    public function cc_init()
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
    }//cc_init function ends


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

        $productData = array(
            'id' => $product->getId(),
            'url' => $product->getProductUrl(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'final_price' => $product->getFinalPrice(),            
            'description' => strip_tags($product->getShortDescription()),
            'sku' => $product->getSku(),
            'rating' => $rating
        );

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (is_object($stock)) {
            $productData['qty'] = $stock->getQty();
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

        $layout = Mage::getSingleton('core/layout');
        if (!$layout) {
            return; 
        }

        $block = $layout->getBlock('product_list');
        if (!$block) {
            Mage::Log("No product_list block Object found ");
            return; 
        }

        $collection = $block->getLoadedProductCollection();

        foreach ($collection as $product) {
            $categoryItem['id'] = $product->getId();
            $categoryItem['name'] = $product->getName();            
            $categoryItems[] = $categoryItem;
        }

        $toolbar = Mage::getBlockSingleton('catalog/product_list_toolbar');
        $category = Mage::getSingleton('catalog/layer')->getCurrentCategory();

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("categoryView");
        $ccView['event_data']['items'] = $categoryItems;

        $ccView['event_data']['params'] = $params;

        if($collection)
            $ccView['event_data']['total_products'] = $collection->getSize();

        if ($toolbar) {
            $ccView['event_data']['sort_by'] = $toolbar->getCurrentOrder()." - ".$toolbar->getCurrentDirection();
            $ccView['event_data']['current_mode'] = $toolbar->getCurrentMode();
        }//if toolbar ends

        if ($category) {
            $ccView['event_data']['category_name'] = $category->getName();
            $ccView['event_data']['category_id'] = $category->getId();
        }

        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccView);
    }//categoryView function ends


    public function searchView($observer)
    {  
        $action = $observer->getAction();
        if (!$action) {
            return; 
        }

        if (!in_array($action->getFullActionName(), array('catalogsearch_result_index'))) {
            return;
        }

        $request = $action->getRequest();
        if (!$request) {
            return; 
        }
        
        $params = $request->getParams();
        $block = Mage::app()->getLayout()->getBlock("search_result_list");
        if ($block) {
            $collection = $block->getLoadedProductCollection();

            if(!$collection)
                return;

            $ccView['event_data']['items_count'] = $collection->getSize();

            $searchResults = array();
            foreach ($collection as $item) {
                $searchResult['id'] = $item->getId();
                $searchResult['sku'] = $item->getSku(); 
                $searchResult['name'] = $item->getName();
                $searchResults[] = $searchResult;                           
            }
            $ccView['event_data']['items'] = $searchResults;
        }

        $search = $observer->getDataObject();
        if ($search) {
            $ccView['event_data']['query'] = $search->getQueryText();
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

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $item->getPrice();
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();      
            $cart[] = $cartItem;
        }

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cartView");
        $ccView['event_data']['current_cart'] = $cart;
        $ccView['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $ccView['event_data']['current_cart']['total'] = $quote->getGrandTotal();
        $ccView['event_data']['current_cart']['base_total'] = $quote->getBaseGrandTotal();

        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);

    } // cartView function ends    

    public function checkoutView($observer)
    {
        $action = $observer->getAction();
        if (!$action) {
            return;
        }
        
        if (!in_array($action->getFullActionName(), array('checkout_onepage_index'))) {
            return;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote) {
            return;
        }

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $item->getPrice();
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();      
            $cart[] = $cartItem;
        }

        $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("checkoutView");
        $ccView['event_data']['current_cart'] = $cart;
        $ccView['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $ccView['event_data']['current_cart']['total'] = $quote->getGrandTotal();
        $ccView['event_data']['current_cart']['base_total'] = $quote->getBaseGrandTotal();        
        $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($ccView);
    } // checkoutView function ends    

    public function loggedIn($observer)
    {
        $customer = $observer->getCustomer();
        $customerData['email'] = $customer->getEmail();
        $customerData['first_name'] = $customer->getFirstname();
        $customerData['last_name'] = $customer->getLastname();
        $customerData['id'] = $customer->getId();

        $customerTotals = Mage::getResourceModel('sales/sale_collection')
             ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
             ->setCustomerFilter($customer)
             ->load()
             ->getTotals();

        $customerData['lifetime_sales'] =  $customerTotals->getLifetime();
        $customerData['num_orders'] =  $customerTotals->getNumOrders();

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedIn");
        $ccData['event_data'] = $customerData;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');
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

    public function addtocart($observer)
    {
        $product = $observer->getProduct();
        $cart['name'] = str_replace("'", "", $product->getName());
        $cart['price'] = $product->getFinalPrice();
        $cart['quantity'] = $product->getQty();
        $cart['id'] = $product->getId();
        $cart['sku'] = $product->getSku();      

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addtocart");
        $ccData['event_data'] = $cart;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//addtocart function ends


    public function removeFromCart($observer)
    {
        $product = $observer->getQuoteItem()->getProduct();
        $cart['name'] = str_replace("'", "", $product->getName());
        $cart['price'] = $product->getFinalPrice();
        $cart['quantity'] = $product->getQty();
        $cart['id'] = $product->getId();
        $cart['sku'] = $product->getSku();      

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCart");
        $ccData['event_data'] = $cart;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//removeFromCart function ends

    public function updateCart()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cartItem = array();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = $item->getPrice();
            $cartItem['quantity'] = $item->getQty();
            $cartItem['id'] = $item->getProductId();
            $cartItem['sku'] = $item->getSku();      
            $cart[] = $cartItem;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("updateCart");
        $ccData['event_data']['current_cart'] = $cart;
        $ccData['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//updateCart function ends

    public function ordered($observer)
    {
        $order = $observer->getOrder();
        foreach ($order->getAllVisibleItems() as $item) {
            $orderItem['name'] = str_replace("'", "", $item->getName());
            $orderItem['price'] = $item->getPrice();
            $orderItem['quantity'] = $item->getQtyOrdered();
            $orderItem['id'] = $item->getId();
            $orderItem['sku'] = $item->getSku();
            $orderItems[] = $orderItem;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("ordered");

        $ccData['event_data']['items'] = $orderItems;
        $ccData['event_data']['coupon_code'] = $order->getCouponCode();
        $ccData['event_data']['total'] = $order->getGrandTotal();
        $ccData['event_data']['base_total'] = $order->getBaseGrandTotal();        
        $ccData['event_data']['shipping_method'] = $order->getShippingDescription();
        $ccData['event_data']['payment_method'] = $order->getPayment()->getMethod(); 
        $ccData['event_data']['status'] = $order->getStatus();
        $ccData['event_data']['shipping_amount'] = $order->getShippingAmount();
        $ccData['event_data']['tax_amount'] = $order->getTaxAmount(); 
        $ccData['event_data']['discount_amount'] = $order->getDiscountAmount();
        $ccData['event_data']['order_id'] = $order->getIncrementId();

        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//ordered function ends

    public function addToWishlist($observer)
    {
        $product  = $observer->getProduct();

        $wishlist['name'] = str_replace("'", "", $product->getName());
        $wishlist['id'] = $product->getId();
        $wishlist['sku'] = $product->getSku();      

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

    $wishlistItems = Mage::helper('wishlist')->getWishlistItemCollection();
    foreach ($wishlistItems as $wishlistItem) {
        $product = $wishlistItem->getProduct();

        $wlist['name'] = str_replace("'", "", $product->getName());
        $wlist['id'] = $product->getId();
        $wlist['qty'] = $wishlistItem->getQty();        
        $wishlist[] = $wlist;
    }
        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistView");
        $ccData['event_data']['current_wishlist'] = $wishlist;
        $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($ccData);
    }//wishlistView function ends

    public function removeFromWishlist($observer)
    { 
        $product  = $observer->getProduct();

        $wishlistItems = Mage::helper('wishlist')->getWishlistItemCollection();
        foreach ($wishlistItems as $wishlistItem) {
            $product = $wishlistItem->getProduct();
            $wlist['name'] = str_replace("'", "", $product->getName());
            $wlist['id'] = $product->getId();
            $wlist['sku'] = $product->getSku();
            $wishlist[] = $wlist;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromWishlist");
        $ccData['event_data']['current_wishlist'] = $wishlist;
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

        foreach ($compareCollection as $product) {
            $compareItem = array();
            $compareItem['sku'] = $product->getSku();
            $compareItem['id'] = $product->getId();
            $compareItem['name'] = $product->getName();            
            $compareItems[] = $compareItem;
        }

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("compareView");
        $ccData['event_data']['current_compare'] = $compareItems;
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
            $status = "cancelled";
        elseif($couponcode == $params['coupon_code'])
            $status = "success";
        elseif($couponcode == '' or !$couponcode)
            $status = "failed";        

        $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("couponInfo");
        $ccData['event_data']['coupon_code'] = $params['coupon_code'];
        $ccData['event_data']['status'] = $status;
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