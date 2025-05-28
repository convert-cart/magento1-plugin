<?php
class Convertcart_Model_Sync_CartController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart/sync_cc')->authorize();
        return parent::preDispatch();
    }

    public function getAction()
    {
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (is_object($quote)) {
                $cart = Mage::Helper('convertcart/sync_cc')->getCartItems($quote);
                $cart['coupon_code'] = $quote->getCouponCode();
                $cart['subtotal'] = Mage::helper('core')->currency($quote->getSubtotal(), false, false);
                $cart['total'] = Mage::helper('core')->currency($quote->getGrandTotal(), false, false);
                $cart['base_total'] = Mage::helper('core')->currency($quote->getBaseGrandTotal(), false, false);
            }

            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($cart);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}