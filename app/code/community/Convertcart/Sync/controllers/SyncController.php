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
        $countData = Mage::getModel('convertcart_sync/sync')->getCountData();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($countData));
    }//countAction ends

    public function attributesAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $attributes = Mage::getModel('convertcart_sync/sync')->getAttributes();
 
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($attributes));
    }

    public function customerAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $customerData = Mage::getModel('convertcart_sync/sync')->getCustomers($params);        

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($customerData));
    }//customerAction ends

    public function orderAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncOrder() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $orderData = Mage::getModel('convertcart_sync/sync')->getOrders($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($orderData));
    }//orderAction ends	

    public function catalogAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $productData = Mage::getModel('convertcart_sync/sync')->getProducts($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($productData));
    }//catalogAction ends

    public function categoryAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $categoryData = Mage::getModel('convertcart_sync/sync')->getCategories($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($categoryData));
    }//categoryAction ends

    public function wishlistAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $wishlistData = Mage::getModel('convertcart_sync/sync')->getWishlist($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($wishlistData));
    }//wishlistAction ends

    public function newsletterAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams(); 
        $newsletterSubscribers = Mage::getModel('convertcart_sync/sync')->getNewsletterSubscribers($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($newsletterSubscribers));
    }
}