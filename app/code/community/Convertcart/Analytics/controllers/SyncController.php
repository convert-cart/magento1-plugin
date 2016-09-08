<?php
class Convertcart_Analytics_SyncController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart_analytics')->authorize();
        return parent::preDispatch();
    }

    //returns count of various items in magento
    public function countAction()
    {
        $countData = Mage::getModel('convertcart_analytics/sync')->getCountData();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($countData));
    }//countAction ends

    public function attributesAction()
    {
        if (Mage::Helper('convertcart_analytics')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $attributes = Mage::getModel('convertcart_analytics/sync')->getAttributes();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($attributes));
    }

    public function customerAction()
    {
        if (Mage::Helper('convertcart_analytics')->canSyncCustomer() == false) { //dont proceed if not enabled
            return;
        }

        $updatedAt = '2011-12-11';
        $limit =10;

        $customers = Mage::getModel('convertcart_analytics/sync')->getCustomers($updatedAt, $limit);
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
        if (Mage::Helper('convertcart_analytics')->canSyncOrder() == false) { //dont proceed if not enabled
            return;
        }

        $updatedAt = '2011-07-29';
        $limit =10;

        $orders = Mage::getModel('convertcart_analytics/sync')->getOrders($updatedAt, $limit);
        $orderData = array();
        foreach ($orders as $key => $incrementId) {
            $orderData[] = Mage::getModel('sales/order_api')->info($incrementId);
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($orderData));
    }//orderAction ends	

    public function catalogAction()
    {
        if (Mage::Helper('convertcart_analytics')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $updatedAt = '2011-07-29';
        $limit =5;
        $storeId =3;

        $productData = Mage::getModel('convertcart_analytics/sync')->getProducts($updatedAt, $limit, $storeId);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($productData));
    }//catalogAction ends

    public function categoryAction()
    {
        if (Mage::Helper('convertcart_analytics')->canSyncCatalog() == false) { //dont proceed if not enabled
            return;
        }

        $storeid=1;

        $rootid     = Mage::app()->getStore($storeid)->getRootCategoryId();
        $categories = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('path', array('like'=> "1/$rootid/%"));

        $categoryData = array();
        foreach ($categories as $category) {
            $categoryData[] = Mage::getModel('catalog/category_api')->info($category->getId());
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($categoryData));
    }
}