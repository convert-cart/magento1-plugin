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
            
            if (!$quote || !$quote->getId()) {
                return;
            }

            $eventData = [
                'items' => $ccModel->getCartItems($quote),
                'coupon_code' => $quote->getCouponCode(),
                'grand_total' => $quote->getGrandTotal(),
                'subtotal' => $quote->getSubtotal(),
                'tax_amount' => $quote->getShippingAddress()->getTaxAmount(),
                'shipping_amount' => $quote->getShippingAddress()->getShippingAmount(),
                'discount_amount' => abs($quote->getShippingAddress()->getDiscountAmount()),
                'items_count' => $quote->getItemsCount(),
                'items_qty' => $quote->getItemsQty()
            ];
            
            $ccModel->storeCcEvents('checkoutView', $eventData);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('Error in trackCheckoutView: ' . $e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track checkout success
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
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

            // Get payment method
            $paymentMethod = '';
            try {
                $payment = $order->getPayment();
                if ($payment) {
                    $paymentMethod = $payment->getMethod();
                }
            } catch (Exception $e) {
                Mage::log('Error getting payment method: ' . $e->getMessage(), null, $this->logFile);
            }

            // Get shipping method
            $shippingMethod = '';
            try {
                $shippingMethod = $order->getShippingDescription();
            } catch (Exception $e) {
                Mage::log('Error getting shipping method: ' . $e->getMessage(), null, $this->logFile);
            }

            $eventData = [
                'orderId' => $order->getIncrementId(),
                'order_email' => $order->getCustomerEmail(),
                'is_guest' => (int)$order->getCustomerIsGuest(),
                'grand_total' => $order->getGrandTotal(),
                'subtotal' => $order->getSubtotal(),
                'tax_amount' => $order->getTaxAmount(),
                'shipping_amount' => $order->getShippingAmount(),
                'discount_amount' => abs($order->getDiscountAmount()),
                'coupon_code' => $order->getCouponCode(),
                'shipping_method' => $shippingMethod,
                'payment_method' => $paymentMethod,
                'status' => $order->getStatus(),
                'currency' => $order->getOrderCurrencyCode(),
                'total' => $order->getGrandTotal(),
                'items' => $ccModel->getOrderItems($order)
            ];
            
            $ccModel->storeCcEvents('checkoutSuccess', $eventData);
            
            // Also store the order ID in the session for later reference
            $session = Mage::getSingleton('core/session');
            $recentOrders = $session->getConvertcartRecentOrders() ?: [];
            $recentOrders[] = $order->getIncrementId();
            $session->setConvertcartRecentOrders(array_slice(array_unique($recentOrders), -10)); // Keep last 10 orders
            
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('Error in trackCheckoutSuccess: ' . $e->getMessage(), null, $this->logFile);
        }
    }
}
