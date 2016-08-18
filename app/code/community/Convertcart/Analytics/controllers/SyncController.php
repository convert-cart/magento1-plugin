<?php
class Convertcart_Analytics_SyncController extends Mage_Core_Controller_Front_Action
{

    public function preDispatch(){
		Mage::helper('convertcart_analytics')->authorize();
        return parent::preDispatch();
    }

	//returns count of various items in magento
	public function countAction(){
		$count_data = Mage::getModel('convertcart_analytics/sync')->getCountData();
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($count_data));
	}//countAction ends

	public function attributesAction(){
        if(Mage::Helper('convertcart_analytics')->canSyncCatalog() == false){ //dont proceed if not enabled
            return;
        }

		$attributes = Mage::getModel('convertcart_analytics/sync')->getAttributes();
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($attributes));		
	}

	public function customerAction(){
        if(Mage::Helper('convertcart_analytics')->canSyncCustomer() == false){ //dont proceed if not enabled
            return;
        }

		$updated_at = '2011-12-11';
		$limit =10;

		$customers = Mage::getModel('convertcart_analytics/sync')->getCustomers($updated_at , $limit);		
	    $customer_data = array();
	    $c=0;
		foreach($customers as $key => $customer_id){
			$customer_data[$c] = Mage::getModel('customer/customer_api')->info($customer_id);
			//we done hash, dont send these fields
			unset($customer_data[$c]['password_hash']);
			unset($customer_data[$c]['rp_token']);
			unset($customer_data[$c]['rp_token_created_at']);
			unset($customer_data[$c]['confirmation']);
			unset($customer_data[$c]['disable_auto_group_change']);						
			unset($customer_data[$c]['reward_update_notification']);						
			unset($customer_data[$c]['reward_warning_notification']);					
			$c++;
		}
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($customer_data));
	}//customerAction ends

	public function orderAction(){
        if(Mage::Helper('convertcart_analytics')->canSyncOrder() == false){ //dont proceed if not enabled
            return;
        }

		$updated_at = '2011-07-29';
		$limit =10;

		$orders = Mage::getModel('convertcart_analytics/sync')->getOrders($updated_at , $limit);		
	    $order_data = array();
		foreach($orders as $key => $increment_id){
			$order_data[] = Mage::getModel('sales/order_api')->info($increment_id);
		}
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($order_data));
	}//orderAction ends	

	public function catalogAction(){
        if(Mage::Helper('convertcart_analytics')->canSyncCatalog() == false){ //dont proceed if not enabled
            return;
        }

		$updated_at = '2011-07-29';
		$limit =5;
		$store_id =3;

		$product_data = Mage::getModel('convertcart_analytics/sync')->getProducts($updated_at, $limit, $store_id);

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($product_data));
	}//catalogAction ends

	public function categoryAction(){
        if(Mage::Helper('convertcart_analytics')->canSyncCatalog() == false){ //dont proceed if not enabled
            return;
        }

		$storeid=1;

		$rootid     = Mage::app()->getStore($storeid)->getRootCategoryId();
		$categories = Mage::getModel('catalog/category')
		    ->getCollection()
		    ->addAttributeToSelect('name')
		    ->addFieldToFilter('path', array('like'=> "1/$rootid/%"));

		$category_data = array();
		foreach ($categories as $category){
			$category_data[] = Mage::getModel('catalog/category_api')->info($category->getId());
		}
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($category_data));
	}
}