<?php

/**
 * Cart tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_CartTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track cart view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCartView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            if (!in_array($action->getFullActionName(), ['checkout_cart_index'])) {
                return;
            }

            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $ccModel = Mage::getModel('convertcart/analytics_cc');
            $cart = $ccModel->getCartItems($quote);
            $currency = null;
            $store = Mage::app()->getStore();
            if (is_object($store)) {
                $currency = $store->getCurrentCurrencyCode();
            }

            $ccView = [];
            $ccView['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('cartView');
            $ccView['event_data'] = [
                'items' => $cart,
                'currency' => $currency,
                'coupon_code' => $quote->getCouponCode(),
                'subtotal' => $ccModel->getValue($quote->getSubtotal()),
                'total' => $ccModel->getValue($quote->getGrandTotal()),
                'base_total' => $ccModel->getValue($quote->getBaseGrandTotal())
            ];
            $ccView['meta_data'] = Mage::getSingleton('convertcart_analytics/cc')->insertMeta(1);
            $ccModel->storeData($ccView);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track coupon info
     *
     * @return void
     */
    public function trackCouponInfo()
    {
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (!is_object($quote)) {
                return;
            }

            $couponCode = $quote->getCouponCode();
            if (empty($couponCode)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('couponInfo');
            $ccData['event_data'] = [
                'code' => $couponCode,
                'items' => $ccModel->getCartItems()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
