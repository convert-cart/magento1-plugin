<?php
class Convertcart_Model_Sync_FindController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart/sync_cc')->authorize();
        return parent::preDispatch();
    }

    public function customerAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $customerData = Mage::getModel('convertcart/sync_cc_find')->getCustomer($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($customerData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function orderAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $orderData = Mage::getModel('convertcart/sync_cc_find')->getOrder($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($orderData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function catalogAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $productData = Mage::getModel('convertcart/sync_cc_find')->getProduct($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function categoryAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $categoryData = Mage::getModel('convertcart/sync_cc_find')->getCategory($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($categoryData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function reviewAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $reviewData = Mage::getModel('convertcart/sync_cc_find')->getReview($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($reviewData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function quoteAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $quoteData = Mage::getModel('convertcart/sync_cc_find')->getQuote($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($quoteData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function wishlistAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $wishlist = Mage::getModel('convertcart/sync_cc_find')->getWishlist($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($wishlist);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function amastyFavoritesAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $favorites = Mage::getmodel('convertcart_sync/find')->getAmastyFavorites($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($favorites);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }
}
