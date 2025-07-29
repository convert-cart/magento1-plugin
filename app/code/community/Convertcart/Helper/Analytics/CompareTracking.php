<?php

/**
 * Compare tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_CompareTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track adding a product to compare list
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackAddToCompare($observer)
    {
        try {
            $product = $observer->getProduct();
            if (!is_object($product)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            
            $compare = [];
            $compare['id']         = $product->getId();
            $compare['sku']        = $product->getSku();
            $compare['name']       = $product->getName();
            $compare['url_key']    = $product->getUrlKey();

            $resource = Mage::getSingleton('catalog/product')->getResource();
            if (is_object($resource)) {
                $imagePath = $resource->getAttributeRawValue($product->getId(), 'image', Mage::app()->getStore());
                $imageUrl  = $ccModel->getImageUrl($imagePath);
                if ($imageUrl != null) {
                    $compare['image'] = $imageUrl;
                }
            }

            $ccData = [];
            $ccData['event_type']   = Mage::helper('convertcart/analytics_cc')->getEventType('addToCompare');
            $ccData['event_data']   = $compare;
            $ccData['meta_data']    = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track removing a product from compare list
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackRemoveFromCompare($observer)
    {
        try {
            $product = $observer->getProduct();
            if (!is_object($product)) {
                return;
            }
            
            $compare = [];
            $compare['id'] = $product->getProductId();
            
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('removeFromCompare');
            $ccData['event_data'] = $compare;
            $ccData['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta();
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track compare view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCompareView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), ['catalog_product_compare_index'])) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('compareView');
            $ccData['event_data'] = [];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
