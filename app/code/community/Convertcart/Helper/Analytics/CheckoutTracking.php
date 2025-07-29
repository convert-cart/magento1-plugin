<?php

/**
 * Checkout tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_CheckoutTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track checkout view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCheckoutView($observer)
    {
        try {
            $action = $observer->getAction();
            if (!is_object($action)) {
                return;
            }

            $validActions = [
                'checkout_onepage_index',
                'onestepcheckout_index_index',
                'onestepcheckout_iosc_index',
                'onestepcheckout_iosc_loadblock'
            ];

            if (!in_array($action->getFullActionName(), $validActions)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('checkoutView');
            $ccData['event_data'] = [
                'items' => $ccModel->getCartItems($quote),
                'coupon_code' => $quote->getCouponCode(),
                'grand_total' => $quote->getGrandTotal()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track checkout success
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCheckoutSuccess($observer)
    {
        try {
            $orderIds = $observer->getOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $order = Mage::getModel('sales/order')->load($orderIds[0]);
            
            if (!$order->getId()) {
                return;
            }

            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('checkoutSuccess');
            $ccData['event_data'] = [
                'order_id' => $order->getIncrementId(),
                'grand_total' => $order->getGrandTotal(),
                'subtotal' => $order->getSubtotal(),
                'tax_amount' => $order->getTaxAmount(),
                'shipping_amount' => $order->getShippingAmount(),
                'discount_amount' => abs($order->getDiscountAmount()),
                'coupon_code' => $order->getCouponCode(),
                'items' => $ccModel->getOrderItems($order)
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
