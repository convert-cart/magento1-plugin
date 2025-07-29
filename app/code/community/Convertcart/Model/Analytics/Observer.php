<?php

class Convertcart_Model_Analytics_Observer
{
    public $logFile = 'cc_analytics.log';

    public function addBlock()
    {
        try {
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData  = $ccModel->getCcData();

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

            $ccModel->clearData();
            foreach ($ccData as $singleEvent) {
                if (!empty($singleEvent['event_data']) and is_array($singleEvent['event_data'])) {
                    $singleEventData = $singleEvent['event_data'];
                } else {
                    $singleEventData = [];
                }

                $singleEventData['ccEvent']   = $singleEvent['event_type'];
                $singleEventData['meta_data'] = $singleEvent['meta_data'];
                $eventData                    = Mage::app()->getLayout()->createBlock('core/template')
                    ->addData(
                        [
                            'cache_lifetime'=> null,
                            'cache_tags'    => [
                                Mage_Core_Model_Store::CACHE_TAG,
                                Mage_Cms_Model_Block::CACHE_TAG,
                                'ccBlock',
                            ],
                            'cache_key' => 'ccEvent',
                        ]
                    );

                $eventData = $eventData->setEventData(json_encode($singleEventData))
                    ->setTemplate('convertcart/event.phtml');
                $beforeBodyEnd->append($eventData);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Handle CC initialization
     *
     * @return void
     */
    public function ccInit()
    {
        try {
            $initData = Mage::helper('cc_analytics/initialization')->handleCcInit();
            if (!$initData) {
                return;
            }

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
    }

    /**
     * Handle homepage view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function homepageView($observer)
    {
        Mage::helper('cc_analytics/viewTracking')->trackHomepageView($observer);
    }

    /**
     * Handle CMS page view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function cmsView($observer)
    {
        Mage::helper('cc_analytics/viewTracking')->trackCmsView($observer);
    }

    /**
     * Handle product view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    /**
     * Handle add to cart event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addToCart($observer)
    {
        Mage::helper('cc_analytics/productTracking')->trackAddToCart($observer);
    }

    /**
     * Handle product view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function productView($observer)
    {
        Mage::helper('cc_analytics/productTracking')->trackProductView($observer);
    }

    /**
     * Check if product view is valid
     *
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    protected function isValidProductView($observer)
    {
        $action = $observer->getAction();
        if (!is_object($action)) {
            return false;
        }

        if (!in_array($action->getFullActionName(), ['catalog_product_view'])) {
            return false;
        }

        $product = Mage::registry('current_product');
        return is_object($product);
    }

    /**
     * Get basic product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @return array
     */
    protected function getBasicProductData($product, $ccModel)
    {
        $store = Mage::app()->getStore();
        $currency = is_object($store) ? $store->getCurrentCurrencyCode() : null;

        return [
        'id'          => $product->getId(),
        'url'         => $product->getProductUrl(),
        'name'        => $product->getName(),
        'price'       => $ccModel->getPrice($product->getPrice()),
        'final_price' => $ccModel->getPrice($product->getFinalPrice()),
        'currency'    => $currency,
        'sku'         => $product->getSku(),
        'type'        => $product->getTypeId(),
        'product_type' => 'simple' // Default value
        ];
    }

    /**
     * Add image URL to product data if available
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @return void
     */
    protected function addImageToProductData($product, &$productData, $ccModel)
    {
        $imageUrl = $ccModel->getImageUrl($product->getImage());
        if ($imageUrl !== null) {
            $productData['image'] = $imageUrl;
        }
    }

    /**
     * Add stock information to product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addStockInfoToProductData($product, &$productData)
    {
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (is_object($stock)) {
            $productData['is_in_stock'] = $stock->getIsInStock();
        }
    }

    /**
     * Process product type specific data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function processProductTypeData($product, &$productData)
    {
        switch ($productData['type']) {
            case 'configurable':
                $this->processConfigurableProduct($product, $productData);
                break;
            case 'bundle':
                $this->processBundleProduct($product, $productData);
                break;
        }
    }

    /**
     * Process configurable product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function processConfigurableProduct($product, &$productData)
    {
        $productData['product_type'] = 'parent';
        $childProducts = Mage::getModel('catalog/product_type_configurable')
        ->getChildrenIds($product->getId());
        if (!empty($childProducts[0])) {
            $productData['child_ids'] = $childProducts[0];
        }
    }

    /**
     * Process bundle product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function processBundleProduct($product, &$productData)
    {
        $priceModel = $product->getPriceModel();
        if (!is_object($priceModel)) {
            return;
        }

        try {
            $pricelist = $priceModel->getTotalPrices($product, null, null, false);
            $productData['product_type'] = 'bundle';
            
            if (isset($pricelist[0])) {
                $productData['lowPrice'] = $pricelist[0];
            }
            if (isset($pricelist[1])) {
                $productData['highPrice'] = $pricelist[1];
            }
        } catch (Error $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track product view event
     *
     * @param array $productData
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @return void
     */
    protected function trackProductView($productData, $ccModel)
    {
        $ccView = [
        'event_type' => Mage::Helper('convertcart/analytics_cc')->getEventType('productView'),
        'event_data' => $productData,
        'meta_data'  => Mage::getSingleton('convertcart_analytics/cc')->insertMeta()
        ];
        $ccModel->storeData($ccView);
    }

    public function categoryView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), ['catalog_category_view'])) {
                return;
            }

            $layer = Mage::getSingleton('catalog/layer');
            if (!is_object($layer)) {
                return;
            }
            
            $ccView = array();
            $ccView['event_data'] = array();
            
            $category = $layer->getCurrentCategory();
            if (is_object($category)) {
                $ccView['event_data']['name'] = $category->getName();
                $ccView['event_data']['id']   = $category->getId();
                $ccView['event_data']['url']  = $category->getUrl();
            }
            
            $toolbar = Mage::getBlockSingleton('catalog/product_list_toolbar');
            if (is_object($toolbar)) {
                $ccView['event_data']['sort_by'] = $toolbar->getCurrentOrder()
                . ' - '
                . $toolbar->getCurrentDirection();
                $ccView['event_data']['current_mode'] = $toolbar->getCurrentMode();
            }

            $ccView['event_type'] = Mage::Helper('convertcart/analytics_cc')->getEventType('categoryView');
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }


    /**
     * Handle search view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function searchView($observer)
    {
        Mage::helper('cc_analytics/viewTracking')->trackSearchView($observer);
    }

    /**
     * Handle compare view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function compareView($observer)
    {
        Mage::helper('cc_analytics/compareTracking')->trackCompareView($observer);
    }

    /**
     * Handle cart view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function cartView($observer)
    {
        Mage::helper('cc_analytics/cartTracking')->trackCartView($observer);
    }



    /**
     * Get and validate order from observer
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Order|false
     */
    protected function getValidOrderFromObserver($observer)
    {
        $action = $observer->getAction();
        if (!is_object($action)) {
            return false;
        }

        $order = $observer->getOrder();
        return is_object($order) ? $order : false;
    }

    /**
     * Prepare order items data
     *
     * @param Mage_Sales_Model_Order $order
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @return array
     */
    protected function prepareOrderItems($order, $ccModel)
    {
        $orderItems = [];
        $store = Mage::app()->getStore();
        $currency = is_object($store) ? $store->getCurrentCurrencyCode() : null;

        foreach ($order->getAllVisibleItems() as $item) {
            $orderItem = $this->prepareOrderItem($item, $ccModel, $store, $currency);
            if (!empty($orderItem)) {
                $orderItems[] = $orderItem;
            }
        }

        return $orderItems;
    }

    /**
     * Prepare single order item data
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @param Mage_Core_Model_Store $store
     * @param string|null $currency
     * @return array
     */
    protected function prepareOrderItem($item, $ccModel, $store, $currency)
    {
        $orderItem = [
        'name' => str_replace("'", '', $item->getName()),
        'price' => $item->getPrice(),
        'currency' => $currency,
        'quantity' => $item->getQtyOrdered(),
        'id' => $item->getProductId(),
        'sku' => $item->getSku(),
        'customOptions' => $ccModel->getOrderItemOptions($item)
        ];

        $this->addProductUrlToOrderItem($item, $orderItem);
        $this->addProductImageToOrderItem($item, $orderItem, $store, $ccModel);

        return $orderItem;
    }

    /**
     * Add product URL to order item
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param array &$orderItem
     * @return void
     */
    protected function addProductUrlToOrderItem($item, &$orderItem)
    {
        $product = $item->getProduct();
        if (is_object($product)) {
            $orderItem['url'] = $product->getProductUrl();
        }
    }

    /**
     * Add product image to order item
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param array &$orderItem
     * @param Mage_Core_Model_Store $store
     * @param Convertcart_Model_Analytics_Cc $ccModel
     * @return void
     */
    protected function addProductImageToOrderItem($item, &$orderItem, $store, $ccModel)
    {
        if (!is_object($store)) {
            return;
        }

        $resource = Mage::getSingleton('catalog/product')->getResource();
        if (!is_object($resource)) {
            return;
        }

        $imagePath = $resource->getAttributeRawValue($item->getProductId(), 'image', $store);
        $imageUrl = $ccModel->getImageUrl($imagePath);
        
        if ($imageUrl !== null) {
            $orderItem['image'] = $imageUrl;
        }
    }

    /**
     * Prepare order data for tracking
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $orderItems
     * @return array
     */
    /**
     * Prepare order data for tracking
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $orderItems
     * @return array
     */
    protected function prepareOrderData($order, $orderItems)
    {
        $ccModel = Mage::getSingleton('convertcart_analytics/cc');
        
        $eventData = [
        'items' => $orderItems,
        'orderId' => $order->getIncrementId(),
        'order_email' => $order->getCustomerEmail(),
        'is_guest' => $order->getCustomerIsGuest(),
        'coupon_code' => $order->getCouponCode(),
        'shipping_method' => $order->getShippingDescription(),
        'payment_method' => $order->getPayment() ? $order->getPayment()->getMethod() : null,
        'status' => $order->getStatus(),
        'currency' => $order->getOrderCurrencyCode(),
        'shipping_amount' => $ccModel->getValue($order->getShippingAmount()),
        'tax_amount' => $ccModel->getValue($order->getTaxAmount()),
        'discount_amount' => $ccModel->getValue($order->getDiscountAmount()),
        'subtotal' => $ccModel->getValue($order->getSubtotal()),
        'total' => $ccModel->getValue($order->getGrandTotal()),
        'base_total' => $ccModel->getValue($order->getBaseGrandTotal()),
        'total_due' => $ccModel->getValue($order->getTotalDue()),
        'base_total_due' => $ccModel->getValue($order->getBaseTotalDue())
        ];

        return [
        'event_type' => Mage::Helper('convertcart/analytics_cc')->getEventType('ordered'),
        'event_data' => $eventData,
        'meta_data' => $ccModel->insertMeta(1)
        ];
    }

    /**
     * Handle wishlist view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function wishlistView($observer)
    {
        Mage::helper('cc_analytics/wishlistTracking')->trackWishlistView($observer);
    }

    /**
     * Handle remove from wishlist event
     *
     * @return void
     */
    public function removeFromWishlist()
    {
        Mage::helper('cc_analytics/wishlistTracking')->trackRemoveFromWishlist(null);
    }

    /**
     * Handle add to compare event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addToCompare($observer)
    {
        Mage::helper('cc_analytics/compareTracking')->trackAddToCompare($observer);
    }

    /**
     * Handle remove from compare event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function removeFromCompare($observer)
    {
        Mage::helper('cc_analytics/compareTracking')->trackRemoveFromCompare($observer);
    }

    public function couponInfo()
    {
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (is_object($quote)) {
                $couponcode = $quote->getData('coupon_code'); //getting applied coupon from cart, if any
            } else {
                $couponcode = null;
            }

            $request = Mage::app()->getRequest();
            if ($request) {
                $params = $request->getParams();
            }

            if (isset($params['remove']) and $params['remove'] == 1) {
                $status = 'couponRemoved';
            } elseif (isset($params['coupon_code']) and $couponcode == $params['coupon_code']) {
                $status = 'couponApplied';
            } elseif (empty($couponcode)) {
                $status = 'couponDenied';
            }

            $ccData = array();
            $ccData['event_type'] = Mage::Helper('convertcart/analytics_cc')->getEventType($status);
            $ccData['event_data']['coupon_code'] = $couponcode;
            $ccData['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Handle newsletter unsubscription event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function newsletterUnsubscribe($observer)
    {
        Mage::helper('cc_analytics/newsletterTracking')->trackNewsletterUnsubscribe($observer);
    }

    /**
     * Handle newsletter subscription event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function newsletterSubscribe($observer)
    {
        Mage::helper('cc_analytics/newsletterTracking')->trackNewsletterSubscribe($observer);
    }

    /**
     * Handle customer register event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function customerRegister($observer)
    {
        Mage::helper('cc_analytics/customerTracking')->trackCustomerRegister($observer);
    }

    /**
     * Handle customer logout event
     *
     * @return void
     */
    public function customerLogout()
    {
        Mage::helper('cc_analytics/customerTracking')->trackCustomerLogout();
    }

    /**
     * Handle customer login event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function customerLogin($observer)
    {
        Mage::helper('cc_analytics/customerTracking')->trackCustomerLogin($observer);
    }

    /**
     * Handle review view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function reviewView($observer)
    {
        Mage::helper('cc_analytics/reviewTracking')->trackReviewView($observer);
    }

    /**
     * Handle checkout view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutView($observer)
    {
        Mage::helper('cc_analytics/checkoutTracking')->trackCheckoutView($observer);
    }

    /**
     * Handle review save event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function reviewSave($observer)
    {
        Mage::helper('cc_analytics/reviewTracking')->trackReviewSave($observer);
    }

    /**
     * Handle checkout success event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutSuccess($observer)
    {
        Mage::helper('cc_analytics/checkoutTracking')->trackCheckoutSuccess($observer);
    }
}
