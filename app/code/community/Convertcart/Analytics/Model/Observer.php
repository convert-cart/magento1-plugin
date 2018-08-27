<?php
class Convertcart_Analytics_Model_Observer
{
    public $logFile = 'cc_analytics.log';

    public function addBlock()
    {
        try {
            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = $cc->getCcData();

            //checking if anything to include
            if ($ccData == false) {
                return;
            }

            $layout = Mage::getSingleton('core/layout');
            if (!is_object($layout)) {
                return;
            }

            $beforeBodyEnd = $layout->getBlock('before_body_end');
            if (!is_object($beforeBodyEnd)) {
                return;
            }

            $cc->clearData();
            foreach ($ccData as $singleEvent) {
                if (!empty($singleEvent['event_data']) and is_array($singleEvent['event_data'])) {
                    $singleEventData = $singleEvent['event_data'];
                } else {
                    $singleEventData = array();
                }
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
                $beforeBodyEnd->append($eventData);
            }//foreach singles_event
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//addBlock function ends

    public function ccInit()
    {
        try {
            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $initData = $cc->getInitScript();
            if($initData == false) //checking if we can include script
                return;

            $layout = Mage::getSingleton('core/layout');
            if (!is_object($layout)) {
                return;
            }

            $head = $layout->getBlock('head');
            if (!is_object($head)) {
                return;
            }
            $head->append($initData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//ccInit function ends

    public function homepageView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('cms_index_index'))) {
                return;
            }

            $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("homepageView");
            $ccView['event_data'] = array();
            $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    } // homepageView function ends

    public function cmsView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('cms_page_view'))) {
                return;
            }

            $cmsInfo = Mage::getSingleton('cms/page');
            if (is_object($cmsInfo)) {
                $ccView['event_data']['title'] = $cmsInfo->getTitle();
                $ccView['event_data']['url_slug'] = $cmsInfo->getIdentifier();
            }

            $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("cmsView");
            $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    } // cmsView function ends


    public function productView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('catalog_product_view'))) {
                return;
            }

            $product = Mage::registry('current_product');
            if(!is_object($product)) {
                return;
            }

            $store = Mage::app()->getStore();
            if(is_object($store)) {
                $currency = $store->getCurrentCurrencyCode();
            }

            $cc = Mage::getModel('convertcart_analytics/cc');
            $productData = array(
                'id' => $product->getId(),
                'url' => $product->getProductUrl(),
                'name' => $product->getName(),
                'price' => $cc->getPrice($product->getPrice()),
                'final_price' => $cc->getPrice($product->getFinalPrice()),
                'currency' => $currency,
                'sku' => $product->getSku()
            );

            if($product->getImage() != null and $product->getImage() != "no_selection")
                $productData['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            if (is_object($stock)) {
                $productData['is_in_stock'] = $stock->getIsInStock();
            }
            // $productData['category_ids'] = $product->getCategoryIds();

            $productData['type'] = $product->getTypeId();
            if ($productData['type'] == "configurable") {
                $productData['product_type'] = "parent";
                $childProducts = Mage::getModel('catalog/product_type_configurable')
                                    ->getChildrenIds($product->getId());
                $productData['child_ids'] = $childProducts[0];
            }
            else {
                $productData['product_type'] = "simple";
            }

            $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("productView");
            $ccView['event_data'] = $productData;
            $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//productView function ends

    public function categoryView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('catalog_category_view'))) {
                return;
            }

            $layer = Mage::getSingleton('catalog/layer');
            if (!is_object($layer)) {
                return;
            }

            $category = $layer->getCurrentCategory();
            if (is_object($category)) {
                $ccView['event_data']['name'] = $category->getName();
                $ccView['event_data']['id'] = $category->getId();
                $ccView['event_data']['url'] = $category->getUrl();
            }

            $toolbar = Mage::getBlockSingleton('catalog/product_list_toolbar');
            if (is_object($toolbar)) {
                $ccView['event_data']['sort_by'] = $toolbar->getCurrentOrder()." - ".$toolbar->getCurrentDirection();
                $ccView['event_data']['current_mode'] = $toolbar->getCurrentMode();
            }//if toolbar ends

            $ccView['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("categoryView");
            $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//categoryView function ends


    public function searchView($observer)
    {
        try {
            $request = Mage::app()->getRequest();
            if ($request) {
                $params = $request->getParams();
                if ($request->isXmlHttpRequest()) //ajax requests, ignore
                    return;
            }

            $ccHelper = Mage::Helper('convertcart_analytics');
            $query = $observer->getDataObject();
            if (is_object($query)) {
                $ccView['event_data']['query'] = $ccHelper->sanitizeParam($query->getQueryText());
                $ccView['event_data']['items_count'] = $query->getNumResults();
            }

            //in some themes / modules above approach doesnt work..
            if (empty($ccView['event_data']['query']) and !empty($params)) {
                $ccView['event_data']['query'] = $ccHelper->sanitizeParam($params['q']);
            }

            $ccView['event_type'] = $ccHelper->getEventType("searchView");
            $ccView['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//searchView function ends

    public function cartView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('checkout_cart_index'))) {
                return;
            }

            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (!is_object($quote)) {
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    } // cartView function ends

    public function checkoutView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            $checkoutActionNames = array('onepagecheckout_index_index', 'checkout_onepage_index', 'onestepcheckout_index_index');
            if (!in_array($action->getFullActionName(), $checkoutActionNames)) {
                return;
            }

            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (!is_object($quote)) {
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    } // checkoutView function ends

    public function loggedIn($observer)
    {
        try {
            $customer = $observer->getCustomer();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedIn");
            $ccData['event_data'] = $cc->getCustomerData($customer);
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//loggedIn function ends

    public function loggedOut($observer)
    {
        try {
            $customer = $observer->getCustomer();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("loggedOut");
            $ccData['event_data'] = $cc->getCustomerData($customer);
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//loggedOut function ends

    public function customerRegister($observer)
    {
        try {
            $customer = $observer->getCustomer();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("customerRegister");
            $ccData['event_data'] = $cc->getCustomerData($customer);
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//customerRegister function ends

    public function customerRegisterOld($observer) //addedto support magento 1.4
    {
        try {
            Mage::getSingleton('convertcart_analytics/cc')->customerRegisterOld();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//customerRegisterOld function ends

    public function customerRegisterCheckOld($observer) //addedto support magento 1.4
    {
        try {
            if ($observer->getQuote()->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)
                return;
            Mage::getSingleton('convertcart_analytics/cc')->customerRegisterOld();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    public function addToCart($observer)
    {
        try {
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//addToCart function ends


    public function removeFromCart($observer)
    {
        try {
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//removeFromCart function ends

    public function ordered($observer)
    {
        try {
            // find a better approach/event
            $orderId = $observer->getData('order_ids');
            if ($orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
            }

            if (!is_object($order)) {
                return;
            }

            // $order = $observer->getOrder();
            $store = Mage::app()->getStore();
            if (is_object($store)) {
                $currency = $store->getCurrentCurrencyCode();
            }

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

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("ordered");
            $ccData['event_data']['orderId'] = $order->getIncrementId();
            $ccData['event_data']['order_email'] = $order->getCustomerEmail();
            $ccData['event_data']['is_guest'] = $order->getCustomerIsGuest();
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//ordered function ends

    public function addToWishlist($observer)
    {
        try {
            $product  = $observer->getProduct();
            if(!is_object($product)) {
                return;
            }

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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//addToWishlist function ends

    public function wishlistView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), array('wishlist_index_index'))) {
                return;
            }

            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistView");
            $ccData['event_data']['items'] = Mage::getSingleton('convertcart_analytics/cc')->getWishlistItems();
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//wishlistView function ends

    public function removeFromWishlist($observer)
    {
        try {
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("wishlistUpdated");
            $ccData['event_data']['items'] = Mage::getSingleton('convertcart_analytics/cc')->getWishlistItems();
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//removeFromWishlist function ends

    public function addToCompare($observer)
    {
        try {
            $product  = $observer->getProduct();
            if(!is_object($product)) {
                return;
            }

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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//addToCompare function ends

    public function removeFromCompare($observer)
    {
        try {
            $product  = $observer->getProduct();
            if(!is_object($product)) {
                return;
            }

            $compare['id'] = $product->getProductId();
            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("removeFromCompare");
            $ccData['event_data'] = $compare;
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//removeFromCompare function ends


    public function compareView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
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
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//compareView ends

    public function couponInfo($observer)
    {
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if(is_object($quote)) {
                $couponcode = $quote->getData('coupon_code'); //getting applied coupon from cart, if any
            } else {
                $couponcode = null;
            }

            $request = Mage::app()->getRequest();
            if ($request) {
                $params = $request->getParams();
            }

            if(isset($params['remove']) and $params['remove'] == 1) {
                $status = "couponRemoved";
            } elseif(isset($params['coupon_code']) and $couponcode == $params['coupon_code']) {
                $status = "couponApplied";
            } elseif(empty($couponcode)) {
                $status = "couponDenied";
            }

            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType($status);
            $ccData['event_data']['coupon_code'] = $couponcode;
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//couponInfo function ends

    public function reviewSave($observer)
    {
        try {
            $review = $observer->getEvent()->getObject();
            if(empty($review)) {
                return;
            }

            $ccData['event_type'] = Mage::Helper('convertcart_analytics')->getEventType("reviewSave");
            $ccData['event_data']['nickname'] = $review['nickname'];
            $ccData['event_data']['title'] = $review['title'];
            $ccData['event_data']['detail'] = $review['detail'];
            $ccData['event_data']['review_id'] = $review['review_id'];
            $ccData['event_data']['product_id'] = $review['entity_pk_value'];
            $ccData['meta_data'] =  Mage::getSingleton('convertcart_analytics/cc')->insertMeta();

            $cc = Mage::getSingleton('convertcart_analytics/cc');
            $cc->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }//reviewSave function ends
}