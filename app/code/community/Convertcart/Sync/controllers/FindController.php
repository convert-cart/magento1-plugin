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
        if (Mage::Helper('convertcart_sync')->canSyncCustomer() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/cc')->getParams();
        $customerData = Mage::getModel('convertcart_sync/find')->getCustomer($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($customerData));
    }//customerAction ends

    public function orderAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncOrder() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/cc')->getParams();
        $orderData = Mage::getModel('convertcart_sync/find')->getOrder($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($orderData));
    }//orderAction ends

    public function catalogAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/cc')->getParams();
        $productData = Mage::getModel('convertcart_sync/find')->getProduct($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($productData));
    }//catalogAction ends

    public function categoryAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/cc')->getParams();
        $categoryData = Mage::getModel('convertcart_sync/find')->getCategory($params);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($categoryData));
    }//categoryAction ends
}