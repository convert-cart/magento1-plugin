<?php
class Convertcart_Model_Sync_SyncController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart/sync_cc')->authorize();
        return parent::preDispatch();
    }

    public function storeAction()
    {
        try {
            $countData = Mage::getModel('convertcart/sync_cc')->getStoreInfo();
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($countData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function attributesAction()
    {
        try {
            $attributes = Mage::getModel('convertcart/sync_cc')->getAttributes();
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($attributes);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function customerAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $customerData = Mage::getModel('convertcart/sync_cc')->getCustomers($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($customerData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function orderAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $orderData = Mage::getModel('convertcart/sync_cc')->getOrders($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($orderData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function catalogAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $productData = Mage::getModel('convertcart/sync_cc')->getProducts($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function productAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $productData = Mage::getModel('convertcart/sync_cc')->getProductsCustomAttr($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function categoryAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $categoryData = Mage::getModel('convertcart/sync_cc')->getCategories($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($categoryData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function wishlistAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $wishlistData = Mage::getModel('convertcart/sync_cc')->getWishlist($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($wishlistData);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function newsletterAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $newsletterSubscribers = Mage::getModel('convertcart/sync_cc')->getNewsletterSubscribers($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($newsletterSubscribers);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function reviewAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $review = Mage::getmodel('convertcart_sync/sync')->getReviews($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($review);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function quoteAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $quoteData = Mage::getModel('convertcart/sync_cc')->getQuote($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($quoteData);
        } catch(Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function amastyFavoritesAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $favorites = Mage::getmodel('convertcart_sync/sync')->getAmastyFavorites($params);
            Mage::Helper('convertcart/sync_cc')->sendSuccessResponse($favorites);
        } catch (Exception $e) {
            Mage::Helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }
}