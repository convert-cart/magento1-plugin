<?php
class Convertcart_Analytics_Model_Sync extends Mage_Core_Model_Session_Abstract
{
	public function getCountData(){
		$count_data = array();

		$count_data['websites'] = $this->getWebsitesData();
		$count_data['products'] = $this->getProductCount();
		$count_data['customers'] = $this->getCustomerCount();
		$count_data['orders'] = $this->getOrderCount();

		$attribute_sets = Mage::getModel('catalog/product_attribute_set_api')->items();

		$count_data['attribute_sets']['total_attribute_sets'] = count($attribute_sets);
		$count_data['attribute_sets']['data'] = $attribute_sets;
		$count_data['attributes'] = array();
		$attribute_set_count = 0;

		foreach($attribute_sets as $attribute_set){
			$attribute_set_count++;
			$items = Mage::getModel('catalog/product_attribute_api')->items($attribute_set['set_id']);
			$count_data['attributes'][$attribute_set_count]['total_attributes'] = count($items);
			$count_data['attributes'][$attribute_set_count]['attribute_set_id'] = $attribute_set['set_id'];
			$count_data['attributes'][$attribute_set_count]['name'] = $attribute_set['name'];			
			$count_data['attributes'][$attribute_set_count]['data'] = $items;
		}//foreach attribute_set ends

		return $count_data;
	}

	public function getCustomerCount(){
		$collection = Mage::getModel("customer/customer")
			->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
			->addAttributeToSelect('id');

		$customers['total_customers'] = $collection->getSize();
		$customers['first_id'] = $collection->getFirstItem()->getId();
		$customers['last_id'] = $collection->getLastItem()->getId();
		return $customers;
	}

	public function getOrderCount(){
		$collection = Mage::getModel("sales/order")
			->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
			->addAttributeToSelect('entity_id');

		$orders['total_orders'] = $collection->getSize();
		$orders['first_id'] = $collection->getFirstItem()->getId();
		$orders['last_id'] = $collection->getLastItem()->getId();
		return $orders;
	}

	public function getProductCount(){
		$collection = Mage::getModel("catalog/product")
			->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
			->addAttributeToSelect('entity_id');

		$products['total_products'] = $collection->getSize();
		$products['first_id'] = $collection->getFirstItem()->getId();
		$products['last_id'] = $collection->getLastItem()->getId();
		return $products;
	}

	public function getWebsitesData(){
		$website_count = 0;

		foreach (Mage::app()->getWebsites() as $website) {
			$store_count = 0;
			$store_data = array();

			$website_count++;
			$website_data[$website_count]['website_code'] = $website->getCode();
			$website_data[$website_count]['website_id'] = $website->getId();

		    foreach ($website->getGroups() as $group) {
		        $stores = $group->getStores();
		        foreach ($stores as $store) {
		        	$store_count++;
		        	$store_data[$store_count]['store_id'] = $store->getId();
		        	$store_data[$store_count]['store_code'] = $store->getCode();   
		        }
		    }
		    $website_data[$website_count]['total_stores'] = $store_count;
		    $website_data[$website_count]['stores'] = $store_data;		    
		}

		$wesbites = array();
		$websites['total_websites'] = $website_count;
		$websites['data'] = $website_data;
		return $websites;
	}//getWebsites function ends

	public function getCustomers($updated_at = "",$limit = 2){
		$updated_at = strtotime($updated_at);
		$updated_at = date('Y-m-d H:i:s', $updated_at); 
		$customers = Mage::getModel("customer/customer")
			->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
			->addAttributeToSelect('id')
			->addAttributeToSelect('email')
			->addAttributeToSelect('updated_at')			
			->addAttributeToFilter('updated_at', array('gteq' =>$updated_at));
			;
		if($limit>1)
			$customers->getSelect()->limit($limit);	

		$customer_ids = array();
		foreach($customers as $customer){
			$customer_ids[] = $customer->getId();						
		}
		return $customer_ids;
	}//getCustomers function ends

	public function getOrders($updated_at = "",$limit = 2){
		$updated_at = strtotime($updated_at);
		$updated_at = date('Y-m-d H:i:s', $updated_at); 
		$orders = Mage::getModel("sales/order")
			->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
			->addAttributeToSelect('entity_id')
			->addAttributeToSelect('increment_id')
			->addAttributeToSelect('updated_at')			
			->addAttributeToFilter('updated_at', array('gteq' =>$updated_at));
			;
		if($limit>1)
			$orders->getSelect()->limit($limit);	

		$order_ids = array();
		foreach($orders as $order){
			$order_ids[] = $order->getIncrementId();						
		}
		return $order_ids;
	}//getOrders function ends

	public function getProducts($updated_at,$limit = 5,$store_id = null){
		$updated_at = strtotime($updated_at);
		$updated_at = date('Y-m-d H:i:s', $updated_at); 
		$products = Mage::getModel("catalog/product")
			->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
			->addAttributeToSelect('*')
			->addAttributeToFilter('updated_at', array('gteq' =>$updated_at))
			->setStoreId($store_id);

		if($limit>1)
			$products->getSelect()->limit($limit);

		$product_data = array();
		$p=0;
		foreach($products as $product){
			$attributes = $product->getAttributes();
			foreach ($attributes as $attribute){
				$attribute_code = $attribute->getAttributeCode();	
				$frontend_input = $attribute->getFrontendInput();

				if($frontend_input == 'multiselect' or $frontend_input == 'select'){				
					$product_data[$p][$attribute_code] = $product->getAttributeText($attribute_code);
				}
				else{
					$product_data[$p][$attribute_code] = $product->getData($attribute_code);
				}
			}//foreach attributes ends
			$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
			$product_data[$p]['stock_data'] = $stock->getData();
			$product_data[$p]['product_url'] = $product->getProductUrl();
			$product_data[$p]['image_url'] = $product->getImage();
			$product_data[$p]['store_ids'] = $product->getStoreIds();
			$p++;
		}
		return $product_data;
	}//getProducts function ends
}