<?php
class Convertcart_Sync_SyncController extends Mage_Core_Controller_Front_Action
{
    public $logFile = 'cc_sync.log';

    public function preDispatch()
    {
        Mage::helper('convertcart_sync')->authorize();
        return parent::preDispatch();
    }

    public function storeAction()
    {
        try {
            $countData = Mage::getModel('convertcart_sync/sync')->getStoreInfo();

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($countData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//countAction ends

    public function attributesAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
                return;
            }

            $attributes = Mage::getModel('convertcart_sync/sync')->getAttributes();

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($attributes));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function customerAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $customerData = Mage::getModel('convertcart_sync/sync')->getCustomers($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($customerData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//customerAction ends

    public function orderAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncOrder() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $orderData = Mage::getModel('convertcart_sync/sync')->getOrders($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($orderData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//orderAction ends	

    public function catalogAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $productData = Mage::getModel('convertcart_sync/sync')->getProducts($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($productData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//catalogAction ends

    public function categoryAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $categoryData = Mage::getModel('convertcart_sync/sync')->getCategories($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($categoryData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//categoryAction ends

    public function wishlistAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $wishlistData = Mage::getModel('convertcart_sync/sync')->getWishlist($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($wishlistData));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }//wishlistAction ends

    public function newsletterAction()
    {
        try {
            if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
                return;
            }

            $params = Mage::getModel('convertcart_sync/cc')->getParams(); 
            $newsletterSubscribers = Mage::getModel('convertcart_sync/sync')->getNewsletterSubscribers($params);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($newsletterSubscribers));
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
            Mage::Helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }
}