<?php

/**
 * View tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_ViewTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track homepage view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackHomepageView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), ['cms_index_index'])) {
                return;
            }
            
            $ccView = [];
            $ccView['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('homepageView');
            $ccView['event_data'] = [];
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track CMS page view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCmsView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), ['cms_page_view'])) {
                return;
            }
            
            $ccView = [];
            $ccView['event_data'] = [];
            
            $cmsInfo = Mage::getSingleton('cms/page');
            if (is_object($cmsInfo)) {
                $ccView['event_data']['title'] = $cmsInfo->getTitle();
                $ccView['event_data']['url_slug'] = $cmsInfo->getIdentifier();
            }
            
            $ccView['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('cmsView');
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track product view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackProductView($observer)
    {
        try {
            // Check if this is a valid product view
            $action = $observer->getControllerAction();
            if (!is_object($action) || $action->getFullActionName() !== 'catalog_product_view') {
                return;
            }

            $product = Mage::registry('current_product');
            if (!$product || !$product->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccView = [
                'event_type' => Mage::helper('convertcart/analytics_cc')->getEventType('productView'),
                'event_data' => $ccModel->getProductData($product),
                'meta_data' => $ccModel->insertMeta()
            ];

            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track category view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCategoryView($observer)
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
            
            $category = $layer->getCurrentCategory();
            if (!is_object($category)) {
                return;
            }
            
            $ccView = [];
            $ccView['event_data'] = [
                'category_id' => $category->getId(),
                'category_name' => $category->getName(),
                'category_url_key' => $category->getUrlKey(),
                'category_url_path' => $category->getUrlPath(),
                'category_level' => $category->getLevel(),
                'category_parent_id' => $category->getParentId()
            ];
            
            // Add sorting and display mode information
            $toolbar = Mage::app()->getLayout()->getBlock('product_list_toolbar');
            if (is_object($toolbar)) {
                $ccView['event_data']['current_order'] = $toolbar->getCurrentOrder();
                $ccView['event_data']['current_direction'] = $toolbar->getCurrentDirection();
                $ccView['event_data']['current_mode'] = $toolbar->getCurrentMode();
            }

            $ccView['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('categoryView');
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track search view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackSearchView($observer)
    {
        try {
            if ($this->isAjaxRequest()) {
                return;
            }

            $ccView = $this->prepareSearchViewData($observer);
            if (empty($ccView['event_data']['query'])) {
                return;
            }

            $this->storeSearchViewData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Check if the current request is an AJAX request
     *
     * @return bool
     */
    protected function isAjaxRequest()
    {
        $request = Mage::app()->getRequest();
        return $request && $request->isXmlHttpRequest();
    }

    /**
     * Prepare search view data from observer
     *
     * @param Varien_Event_Observer $observer
     * @return array
     */
    protected function prepareSearchViewData($observer)
    {
        $ccHelper = Mage::helper('convertcart/analytics_cc');
        $query = $observer->getDataObject();
        
        $ccView = [];
        $ccView['event_data'] = [];
        
        $this->extractSearchQueryFromObject($query, $ccView, $ccHelper);
        $this->extractSearchQueryFromRequest($ccView, $ccHelper);
        
        if (!empty($ccView['event_data']['query'])) {
            $ccView['event_type'] = $ccHelper->getEventType('searchView');
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
        }
        
        return $ccView;
    }

    /**
     * Extract search query from query object
     *
     * @param mixed $query
     * @param array &$ccView
     * @param Mage_Core_Helper_Abstract $ccHelper
     * @return void
     */
    protected function extractSearchQueryFromObject($query, &$ccView, $ccHelper)
    {
        if (!is_object($query)) {
            return;
        }

        $searchQuery = $query->getQueryText();
        if (empty($searchQuery)) {
            return;
        }

        $ccView['event_data']['query'] = $ccHelper->sanitizeParam($searchQuery);
        $ccView['event_data']['num_results'] = $query->getNumResults();
        $ccView['event_data']['is_processed'] = $query->getIsProcessed();
    }

    /**
     * Extract search query from request parameters if not already set
     *
     * @param array &$ccView
     * @param Mage_Core_Helper_Abstract $ccHelper
     * @return void
     */
    protected function extractSearchQueryFromRequest(&$ccView, $ccHelper)
    {
        if (!empty($ccView['event_data']['query'])) {
            return;
        }

        $params = Mage::app()->getRequest()->getParams();
        if (!empty($params['q'])) {
            $ccView['event_data']['query'] = $ccHelper->sanitizeParam($params['q']);
        }
    }

    /**
     * Store search view data
     *
     * @param array $ccView
     * @return void
     */
    protected function storeSearchViewData($ccView)
    {
        $ccModel = Mage::getSingleton('convertcart_analytics/cc');
        $ccModel->storeData($ccView);
    }

    /**
     * Track checkout view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    /**
     * Track checkout view event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCheckoutView($observer)
    {
        try {
            $order = $this->getValidOrderFromObserver($observer);
            if (!$order) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $orderItems = $this->prepareOrderItems($order, $ccModel);
            $ccData = $this->prepareOrderData($order, $orderItems);
            
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
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

        $fullActionName = $action->getFullActionName();
        if (!in_array($fullActionName, ['checkout_onepage_success', 'onestepcheckout_index_success'])) {
            return false;
        }

        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!$orderId) {
            return false;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            return false;
        }

        return $order;
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
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $product = $item->getProduct();
            if (!$product) {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
            }

            $orderItems[] = [
                'product_id' => $product->getId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'qty' => $item->getQtyOrdered(),
                'row_total' => $item->getRowTotal(),
                'product_type' => $item->getProductType(),
                'product_data' => $ccModel->getProductData($product)
            ];
        }

        return $orderItems;
    }

    /**
     * Prepare order data for tracking
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $orderItems
     * @return array
     */
    protected function prepareOrderData($order, $orderItems)
    {
        $ccHelper = Mage::helper('convertcart/analytics_cc');
        
        return [
            'event_type' => $ccHelper->getEventType('checkoutView'),
            'event_data' => [
                'order_id' => $order->getIncrementId(),
                'order_status' => $order->getStatus(),
                'order_state' => $order->getState(),
                'order_date' => $order->getCreatedAt(),
                'customer_id' => $order->getCustomerId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_group_id' => $order->getCustomerGroupId(),
                'total_item_count' => $order->getTotalItemCount(),
                'total_qty_ordered' => $order->getTotalQtyOrdered(),
                'base_subtotal' => $order->getBaseSubtotal(),
                'base_tax_amount' => $order->getBaseTaxAmount(),
                'base_shipping_amount' => $order->getBaseShippingAmount(),
                'base_discount_amount' => abs($order->getBaseDiscountAmount()),
                'base_grand_total' => $order->getBaseGrandTotal(),
                'shipping_method' => $order->getShippingMethod(),
                'shipping_description' => $order->getShippingDescription(),
                'payment_method' => $order->getPayment()->getMethod(),
                'coupon_code' => $order->getCouponCode(),
                'items' => $orderItems
            ],
            'meta_data' => Mage::getSingleton('convertcart_analytics/cc')->insertMeta()
        ];
    }
}
