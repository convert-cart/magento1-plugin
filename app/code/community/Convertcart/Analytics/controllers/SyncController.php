<?php
class Convertcart_Analytics_SyncController extends Mage_Core_Controller_Front_Action
{
	//returns count of various items in magento
	public function countAction(){
		$count_data = Mage::getModel('convertcart_analytics/sync')->getCountData();
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($count_data));
	}//countAction ends

	public function customerAction(){
		$updated_at = '2011-12-11';
		$limit =10;

		$customers = Mage::getModel('convertcart_analytics/sync')->getCustomers($updated_at , $limit);		
	    $customer_data = array();
		foreach($customers as $key => $customer_id){
			$customer_data[] = Mage::getModel('customer/customer_api')->info($customer_id);
		}
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($customer_data));
	}//customerAction ends

	public function orderAction(){
		$updated_at = '2016-07-29';
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
		$updated_at = '2015-07-29';
		$limit =5;
		$store_id =3;

		$product_data = Mage::getModel('convertcart_analytics/sync')->getProducts($updated_at, $limit, $store_id);
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($product_data));
	}//catalogAction ends
}