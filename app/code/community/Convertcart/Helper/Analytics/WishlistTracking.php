<?php

/**
 * Wishlist tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_WishlistTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track adding a product to wishlist
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackAddToWishlist($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            if (!is_object($product) || !$product->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [
                'event_type' => Mage::helper('convertcart/analytics_cc')->getEventType('addToWishlist'),
                'event_data' => $ccModel->getProductData($product),
                'meta_data' => $ccModel->insertMeta()
            ];

            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track wishlist view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackWishlistView($observer)
    {
        try {
            $wishlist = $observer->getEvent()->getWishlist();
            if (!is_object($wishlist)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [
                'event_type' => Mage::helper('convertcart/analytics_cc')->getEventType('wishlistView'),
                'event_data' => [
                    'wishlist_id' => $wishlist->getId(),
                    'items_count' => $wishlist->getItemsCount(),
                    'customer_id' => $wishlist->getCustomerId()
                ],
                'meta_data' => $ccModel->insertMeta()
            ];

            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track removing a product from wishlist
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackRemoveFromWishlist($observer)
    {
        try {
            $item = $observer->getEvent()->getItem();
            if (!is_object($item) || !$item->getId()) {
                return;
            }

            $product = $item->getProduct();
            if (!is_object($product) || !$product->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [
                'event_type' => Mage::helper('convertcart/analytics_cc')->getEventType('removeFromWishlist'),
                'event_data' => $ccModel->getProductData($product),
                'meta_data' => $ccModel->insertMeta()
            ];

            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
