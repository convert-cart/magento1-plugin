<?php
class Convertcart_Sync_SyncController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart_sync')->authorize();
        return parent::preDispatch();
    }

    public function storeAction()
    {
        try {
            $countData = Mage::getModel('convertcart_sync/sync')->getStoreInfo();
            Mage::Helper('convertcart_sync')->sendSuccessResponse($countData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function attributesAction()
    {
        try {
            $attributes = Mage::getModel('convertcart_sync/sync')->getAttributes();
            Mage::Helper('convertcart_sync')->sendSuccessResponse($attributes);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function customerAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $customerData = Mage::getModel('convertcart_sync/sync')->getCustomers($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($customerData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function orderAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $orderData = Mage::getModel('convertcart_sync/sync')->getOrders($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($orderData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function catalogAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $productData = Mage::getModel('convertcart_sync/sync')->getProducts($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function productAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $productData = Mage::getModel('convertcart_sync/sync')->getProductsCustomAttr($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function categoryAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $categoryData = Mage::getModel('convertcart_sync/sync')->getCategories($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($categoryData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function wishlistAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $wishlistData = Mage::getModel('convertcart_sync/sync')->getWishlist($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($wishlistData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function newsletterAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $newsletterSubscribers = Mage::getModel('convertcart_sync/sync')->getNewsletterSubscribers($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($newsletterSubscribers);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function reviewAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $review = Mage::getmodel('convertcart_sync/sync')->getReviews($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($review);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function cartAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $cartdata = Mage::getModel('convertcart_sync/sync')->getQuote($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($cartdata);
        } catch(Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }
}