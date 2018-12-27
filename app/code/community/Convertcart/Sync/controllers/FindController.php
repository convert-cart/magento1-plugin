<?php
class Convertcart_Sync_FindController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart_sync')->authorize();
        return parent::preDispatch();
    }

    public function customerAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $customerData = Mage::getModel('convertcart_sync/find')->getCustomer($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($customerData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function orderAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $orderData = Mage::getModel('convertcart_sync/find')->getOrder($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($orderData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function catalogAction()
    {
        try {

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $productData = Mage::getModel('convertcart_sync/find')->getProduct($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($productData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function categoryAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $categoryData = Mage::getModel('convertcart_sync/find')->getCategory($params);
            Mage::Helper('convertcart_sync')->sendSuccessResponse($categoryData);
        } catch (Exception $e) {
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }
}