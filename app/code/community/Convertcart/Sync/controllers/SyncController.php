<?php
class Convertcart_Sync_SyncController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        // Mage::helper('convertcart_sync')->authorize();
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
        $customers = Mage::getModel('convertcart_sync/sync')->getCustomers($params);

        $customerData = array();
        $c=0;
        foreach ($customers as $key => $customerId) {
            $customerData[$c] = Mage::getModel('customer/customer_api')->info($customerId);
            //we done hash, dont send these fields
            unset($customerData[$c]['password_hash']);
            unset($customerData[$c]['rp_token']);
            unset($customerData[$c]['rp_token_created_at']);
            unset($customerData[$c]['confirmation']);
            unset($customerData[$c]['disable_auto_group_change']);
            unset($customerData[$c]['reward_update_notification']);
            unset($customerData[$c]['reward_warning_notification']);
            $c++;
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($customerData));
    }//customerAction ends

    public function orderAction()
    {
        if (Mage::Helper('convertcart_sync')->canSyncOrder() == false) { //dont proceed if not enabled
            return;
        }

        $params = Mage::getModel('convertcart_sync/sync')->getParams();
        $orders = Mage::getModel('convertcart_sync/sync')->getOrders($params);

        $orderData = array();
        foreach ($orders as $key => $incrementId) {
            $orderData[] = Mage::getModel('sales/order_api')->info($incrementId);
        }
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
    }
}