<?php

/**
 * Product tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_ProductTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track product view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackProductView($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            if (!is_object($product)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('productView');
            $ccData['event_data'] = [
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getFinalPrice(),
                'url' => $product->getProductUrl()
            ];

            // Add product image if available
            $imageUrl = $ccModel->getImageUrl($product->getImage());
            if ($imageUrl) {
                $ccData['event_data']['image'] = $imageUrl;
            }

            // Add categories if available
            $categoryIds = $product->getCategoryIds();
            if (!empty($categoryIds)) {
                $categories = [];
                foreach ($categoryIds as $categoryId) {
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    if ($category->getId()) {
                        $categories[] = $category->getName();
                    }
                }
                if (!empty($categories)) {
                    $ccData['event_data']['categories'] = $categories;
                }
            }

            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track product add to cart
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackAddToCart($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $request = $observer->getEvent()->getRequest();
            
            if (!is_object($product) || !is_object($request)) {
                return;
            }

            $qty = $request->getParam('qty', 1);
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('addToCart');
            $ccData['event_data'] = [
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getFinalPrice(),
                'quantity' => (int)$qty,
                'url' => $product->getProductUrl()
            ];

            // Add product image if available
            $imageUrl = $ccModel->getImageUrl($product->getImage());
            if ($imageUrl) {
                $ccData['event_data']['image'] = $imageUrl;
            }

            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
