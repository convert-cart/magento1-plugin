<?php
class Convertcart_Analytics_Model_Observer
{
    public function addBlock()
    {
        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $cc_data = $cc->getData();

        //checking if anything to include
        if($cc_data==false){
            return;
        }

        $layout = Mage::getSingleton('core/layout');
        if(!$layout){
            Mage::Log("No Layout Object in " . __METHOD__);
            return;
        }
        
        $before_body_end = $layout->getBlock('before_body_end');
        if(!$before_body_end){
            Mage::Log("No before body end in " . __METHOD__);
            return;
        }

        $cc->clearData();
        foreach($cc_data as $single_event){
            $cc_event_data = Mage::app()->getLayout()->createBlock('core/template')
                              ->setEventType(json_encode($single_event['event_type']))
                              ->setEventData(json_encode($single_event['event_data']))
                              ->setMetaData(json_encode($single_event['meta_data']))
                              ->setTemplate('convertcart/event.phtml');
            $layout->getBlock('before_body_end')->append($cc_event_data);
        }//foreach singles_event
    }//addBlock function ends

    public function cc_init(){
        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $cc_init_data = $cc->getInitScript();
        if($cc_init_data == false) //checking if we can include script
            return;

        $layout = Mage::getSingleton('core/layout');
        if(!$layout){
            Mage::Log("No Layout Object in " . __METHOD__);
            return;
        }
        
        $head = $layout->getBlock('head');
        if(!$head){
            Mage::Log("No before body end in " . __METHOD__);
            return;
        }
        $layout->getBlock('head')->append($cc_init_data);
    }//cc_init function ends


    public function homepageView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();        
        if (!in_array($action->getFullActionName(), array('cms_index_index'))){
            return;
        }

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("homepageView");
        $cc_view['event_data'] = '';
        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);

    } // homepageView function ends

    public function cmsView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }

        $params = $request->getParams();        
        if (!in_array($action->getFullActionName(), array('cms_page_view'))){
            return;
        }

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cmsView");
        $cc_view['event_data']['title'] = Mage::getSingleton('cms/page')->getTitle();
        $cc_view['event_data']['url_slug'] = Mage::getSingleton('cms/page')->getIdentifier();        
        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);

    } // cmsView function ends


    public function productView($observer)
    {
        $action = $observer->getAction();
        if(!$action){ return; }
        
        $request = $action->getRequest();
        if(!$request) { return; }
        
        $params = $request->getParams();
        
        if (!in_array($action->getFullActionName(), array('catalog_product_view'))){
            return;
        }

        $product = Mage::registry('current_product');

        if(!is_object($product))
            return;

        $product_data = array(
            'id' => $product->getId(),
            'url' => $product->getProductUrl(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'final_price' => $product->getFinalPrice(),            
            'description' => strip_tags($product->getShortDescription()),
            'sku' => $product->getSku()
        );

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if(is_object($stock)){
            $product_data['qty'] = $stock->getQty();
            $product_data['is_in_stock'] = $stock->getIsInStock();
        }

        $categories = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('entity_id', array('in'=>$product->getCategoryIds()));

        $product_data['category'] = array();
        //$product_data['category_ids'] = $product->getCategoryIds();
        $c=0;
        foreach($categories as $category)
        {
            $product_data['category'][$c]['name'] = $category->getName();
            $product_data['category'][$c]['id'] = $category->getId();            
            $c++;            
        }

        $product_data['type'] = $product->getTypeId();

        if($product_data['type'] == "configurable"){
            $product_data['product_type'] = "parent";
            $child_products = Mage::getModel('catalog/product_type_configurable')
                                ->getChildrenIds($product->getId());            
            $product_data['child_ids'] = $child_products[0];
        }
        else
            $product_data['product_type'] = "simple";

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("productView");
        $cc_view['event_data'] = $product_data;
        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);
    }//productView function ends

    public function categoryView($observer)
    {
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();


        if (!in_array($action->getFullActionName(), array('catalog_category_view'))){
            return;
        }

        $layout = Mage::getSingleton('core/layout');
        if(!$layout) {
            return; 
        }

        $block = $layout->getBlock('product_list');
        if(!$block) {
            Mage::Log("No product_list block Object in ".__METHOD__);
            return; 
        }

        $collection = $block->getLoadedProductCollection();

        foreach($collection as $product) {          
            $category_item['id'] = $product->getId();
            $category_item['name'] = $product->getName();            
            $category_items[] = $category_item;
        }

        $toolbar = Mage::getBlockSingleton('catalog/product_list_toolbar');
        $category = Mage::getSingleton('catalog/layer')->getCurrentCategory();

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("categoryView");
        $cc_view['event_data']['items'] = $category_items;

        $cc_view['event_data']['params'] = $params;

        if($collection)
            $cc_view['event_data']['total_products'] = $collection->getSize();

        if($toolbar){
            $cc_view['event_data']['sort_by'] = $toolbar->getCurrentOrder()." - ".$toolbar->getCurrentDirection();
            $cc_view['event_data']['current_mode'] = $toolbar->getCurrentMode();
        }//if toolbar ends

        if($category){
            $cc_view['event_data']['category_name'] = $category->getName();
            $cc_view['event_data']['category_id'] = $category->getId();
        }

        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_view);
    }//categoryView function ends


    public function searchView($observer){
        $search = $observer->getDataObject();

        if($search){
            $cc_view['event_data']['number_results'] = $search->getNumResults();
            $cc_view['event_data']['query'] = $search->getQueryText();
        }

        //in some themes / modules above approach doesnt work..

        $request = Mage::app()->getRequest();
        if($request){ 
            $params = $request->getParams();
        }

        if(!$cc_view['event_data']['query'] and $params){
            $cc_view['event_data']['query'] = $params['q'];
        }
        $cc_view['event_data']['params'] = $params;

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("searchView");
        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();
        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);

    }//searchView function ends

    public function cartView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();        
        if (!in_array($action->getFullActionName(), array('checkout_cart_index'))){
            return;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if(!$quote){
            return;
        }

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cart_item = array();
            $cart_item['name'] = str_replace("'","",$item->getName());
            $cart_item['price'] = $item->getPrice();
            $cart_item['quantity'] = $item->getQty();
            $cart_item['id'] = $item->getProductId();
            $cart_item['sku'] = $item->getSku();      
            $cart[] = $cart_item;
        }

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cartView");
        $cc_view['event_data']['current_cart'] = $cart;
        $cc_view['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $cc_view['event_data']['current_cart']['total'] = $quote->getGrandTotal();
        $cc_view['event_data']['current_cart']['base_total'] = $quote->getBaseGrandTotal();

        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);

    } // cartView function ends    

    public function checkoutView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();
        if (!in_array($action->getFullActionName(), array('checkout_onepage_index'))){
            return;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if(!$quote){
            return;
        }

        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cart_item = array();
            $cart_item['name'] = str_replace("'","",$item->getName());
            $cart_item['price'] = $item->getPrice();
            $cart_item['quantity'] = $item->getQty();
            $cart_item['id'] = $item->getProductId();
            $cart_item['sku'] = $item->getSku();      
            $cart[] = $cart_item;
        }

        $cc_view['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("checkoutView");
        $cc_view['event_data']['current_cart'] = $cart;
        $cc_view['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $cc_view['event_data']['current_cart']['total'] = $quote->getGrandTotal();
        $cc_view['event_data']['current_cart']['base_total'] = $quote->getBaseGrandTotal();        
        $cc_view['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');         
        $cc->storeData($cc_view);
    } // checkoutView function ends    

    public function loggedIn($observer){
        $customer = $observer->getCustomer();
        $customer_data['email'] = $customer->getEmail();
        $customer_data['first_name'] = $customer->getFirstname();
        $customer_data['last_name'] = $customer->getLastname();
        $customer_data['id'] = $customer->getId();

        $customerTotals = Mage::getResourceModel('sales/sale_collection')
             ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
             ->setCustomerFilter($customer)
             ->load()
             ->getTotals();

        $customer_data['lifetime_sales'] =  $customerTotals->getLifetime();
        $customer_data['num_orders'] =  $customerTotals->getNumOrders();

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedIn");
        $cc_data['event_data'] = $customer_data;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');
        $cc->storeData($cc_data);
    }//loggedIn function ends

    public function loggedOut($observer){
        $customer = $observer->getCustomer();
        $customer_data['email'] = $customer->getEmail();
        $customer_data['first_name'] = $customer->getFirstname();
        $customer_data['last_name'] = $customer->getLastname();
        $customer_data['id'] = $customer->getId();

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedOut");
        $cc_data['event_data'] = $customer_data;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//loggedOut function ends

    public function customerRegister($observer){
        $customer = $observer->getCustomer();
        $customer_data['email'] = $customer->getEmail();
        $customer_data['first_name'] = $customer->getFirstname();
        $customer_data['last_name'] = $customer->getLastname();
        $customer_data['id'] = $customer->getId();

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("customerRegister");
        $cc_data['event_data'] = $customer_data;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//customerRegister function ends

    public function addtocart($observer){
        $product = $observer->getProduct();
        $cart['name'] = str_replace("'","",$product->getName());
        $cart['price'] = $product->getFinalPrice();
        $cart['quantity'] = $product->getQty();
        $cart['id'] = $product->getId();
        $cart['sku'] = $product->getSku();      

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addtocart");
        $cc_data['event_data'] = $cart;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//addtocart function ends


    public function removeFromCart($observer){
        $product = $observer->getQuoteItem()->getProduct();
        $cart['name'] = str_replace("'","",$product->getName());
        $cart['price'] = $product->getFinalPrice();
        $cart['quantity'] = $product->getQty();
        $cart['id'] = $product->getId();
        $cart['sku'] = $product->getSku();      

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCart");
        $cc_data['event_data'] = $cart;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//removeFromCart function ends

    public function updateCart(){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        $cart = array();

        foreach ($cartItems as $item) {
            $cart_item = array();
            $cart_item['name'] = str_replace("'","",$item->getName());
            $cart_item['price'] = $item->getPrice();
            $cart_item['quantity'] = $item->getQty();
            $cart_item['id'] = $item->getProductId();
            $cart_item['sku'] = $item->getSku();      
            $cart[] = $cart_item;
        }

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("updateCart");
        $cc_data['event_data']['current_cart'] = $cart;
        $cc_data['event_data']['current_cart']['coupon_code'] = $quote->getCouponCode();
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//updateCart function ends

    public function ordered($observer){
        $order = $observer->getOrder();
        foreach($order->getAllVisibleItems() as $item){
            $order_item['name'] = str_replace("'","",$item->getName());
            $order_item['price'] = $item->getPrice();
            $order_item['quantity'] = $item->getQtyOrdered();
            $order_item['id'] = $item->getId();
            $order_item['sku'] = $item->getSku();
            $order_items[] = $order_item;
        }

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("ordered");

        $cc_data['event_data']['items'] = $order_items;
        $cc_data['event_data']['coupon_code'] = $order->getCouponCode();
        $cc_data['event_data']['total'] = $order->getGrandTotal();
        $cc_data['event_data']['base_total'] = $order->getBaseGrandTotal();        
        $cc_data['event_data']['shipping_method'] = $order->getShippingDescription();
        $cc_data['event_data']['payment_method'] = $order->getPayment()->getMethod(); 
        $cc_data['event_data']['status'] = $order->getStatus();
        $cc_data['event_data']['shipping_amount'] = $order->getShippingAmount();
        $cc_data['event_data']['tax_amount'] = $order->getTaxAmount(); 
        $cc_data['event_data']['discount_amount'] = $order->getDiscountAmount();
        $cc_data['event_data']['order_id'] = $order->getIncrementId();

        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();
        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//ordered function ends

    public function addToWishlist($observer){
        $product  = $observer->getProduct();

        $wishlist['name'] = str_replace("'","",$product->getName());
        $wishlist['id'] = $product->getId();
        $wishlist['sku'] = $product->getSku();      

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addToWishlist");
        $cc_data['event_data'] = $wishlist;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//addToWishlist function ends

    public function wishlistView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();        
        if (!in_array($action->getFullActionName(), array('wishlist_index_index'))){
            return;
        }

    $wishlist_items = Mage::helper('wishlist')->getWishlistItemCollection();
    foreach ($wishlist_items as $wishlist_item) {
        $product = $wishlist_item->getProduct();

        $wlist['name'] = str_replace("'","",$product->getName());
        $wlist['id'] = $product->getId();
        $wlist['qty'] = $wishlist_item->getQty();        
        $wishlist[] = $wlist;
    }
        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistView");
        $cc_data['event_data']['current_wishlist'] = $wishlist;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//wishlistView function ends

    public function removeFromWishlist($observer){ 
        $product  = $observer->getProduct();

        $wishlist_items = Mage::helper('wishlist')->getWishlistItemCollection();
        foreach ($wishlist_items as $wishlist_item) {
            $product = $wishlist_item->getProduct();
            $wlist['name'] = str_replace("'","",$product->getName());
            $wlist['id'] = $product->getId();
            $wlist['sku'] = $product->getSku();
            $wishlist[] = $wlist;
        }

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromWishlist");
        $cc_data['event_data']['current_wishlist'] = $wishlist;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//removeFromWishlist function ends

    public function addToCompare($observer){
        $product  = $observer->getProduct();

        $compare['name'] = str_replace("'","",$product->getName());
        $compare['id'] = $product->getId();
        $compare['sku'] = $product->getSku();      

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("addToCompare");
        $cc_data['event_data'] = $compare;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//addToCompare function ends

    public function removeFromCompare($observer){
        $product  = $observer->getProduct();
        $compare['id'] = $product->getProductId();

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCompare");
        $cc_data['event_data'] = $compare;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//removeFromCompare function ends


    public function compareView($observer){
        $action = $observer->getAction();
        if(!$action){ 
            return; 
        }
        
        $request = $action->getRequest();
        if(!$request){ 
            return; 
        }
        
        $params = $request->getParams();
        if (!in_array($action->getFullActionName(), array('catalog_product_compare_index'))){
            return;
        }

        $compare_collection = Mage::helper('catalog/product_compare')->getItemCollection();
        $compare_items = array();

        foreach($compare_collection as $product){
            $compare_item = array();
            $compare_item['sku'] = $product->getSku();
            $compare_item['id'] = $product->getId();
            $compare_item['name'] = $product->getName();            
            $compare_items[] = $compare_item;
        }

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("compareView");
        $cc_data['event_data']['current_compare'] = $compare_items;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
   }//compareView ends

    public function couponInfo($observer){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $couponcode = $quote->getData('coupon_code');

        $request = Mage::app()->getRequest();
        if($request){ 
            $params = $request->getParams();
        }

        if($params['remove'] == 1)
            $status = "cancelled";
        elseif($couponcode == $params['coupon_code'])
            $status = "success";
        elseif($couponcode == '' or !$couponcode)
            $status = "failed";        

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("couponInfo");
        $cc_data['event_data']['coupon_code'] = $params['coupon_code'];
        $cc_data['event_data']['status'] = $status;
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//couponInfo function ends

    public function reviewSave($observer){
        $review=$observer->getEvent()->getObject();

        if(!$review)
            return;

        $cc_data['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("reviewSave");
        $cc_data['event_data']['nickname'] = $review['nickname'];
        $cc_data['event_data']['title'] = $review['title'];
        $cc_data['event_data']['detail'] = $review['detail'];    
        $cc_data['event_data']['review_id'] = $review['review_id'];
        $cc_data['event_data']['product_id'] = $review['entity_pk_value'];
        $cc_data['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertmeta();

        $cc = Mage::getSingleton('convertcart_analytics/cc');  
        $cc->storeData($cc_data);
    }//reviewSave function ends
}