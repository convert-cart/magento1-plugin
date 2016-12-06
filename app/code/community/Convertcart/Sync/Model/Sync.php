<?php
class Convertcart_Sync_Model_Sync extends Mage_Core_Model_Session_Abstract
{
    public function getCountData()
    {
        $countData = array();

        $countData['websites'] = $this->getWebsitesData();
        $countData['products'] = $this->getProductCount();
        $countData['customers'] = $this->getCustomerCount();
        $countData['orders'] = $this->getOrderCount();

        return $countData;
    }

    public function getCustomerCount()
    {
        $collection = Mage::getModel("customer/customer")
            ->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
            ->addAttributeToSelect('id');

        $customers['total_customers'] = $collection->getSize();
        $customers['first_id'] = $collection->getFirstItem()->getId();
        $customers['last_id'] = $collection->getLastItem()->getId();
        return $customers;
    }

    public function getOrderCount()
    {
        $collection = Mage::getModel("sales/order")
            ->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
            ->addAttributeToSelect('entity_id');

        $orders['total_orders'] = $collection->getSize();
        $orders['first_id'] = $collection->getFirstItem()->getId();
        $orders['last_id'] = $collection->getLastItem()->getId();
        return $orders;
    }

    public function getProductCount()
    {
        $collection = Mage::getModel("catalog/product")
            ->getCollection()
            ->addAttributeToSort('entity_id', 'asc')
            ->addAttributeToSelect('entity_id');

        $products['total_products'] = $collection->getSize();
        $products['first_id'] = $collection->getFirstItem()->getId();
        $products['last_id'] = $collection->getLastItem()->getId();
        return $products;
    }

    public function getWebsitesData()
    {
        $websiteCount = 0;

        foreach (Mage::app()->getWebsites() as $website) {
            $storeCount = 0;
            $storeData = array();

            $websiteCount++;
            $websiteData[$websiteCount]['website_code'] = $website->getCode();
            $websiteData[$websiteCount]['website_id'] = $website->getId();

            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeCount++;
                    $storeData[$storeCount]['store_id'] = $store->getId();
                    $storeData[$storeCount]['store_code'] = $store->getCode();   
                }
            }
            $websiteData[$websiteCount]['total_stores'] = $storeCount;
            $websiteData[$websiteCount]['stores'] = $storeData; 
        }

        $wesbites = array();
        $websites['total_websites'] = $websiteCount;
        $websites['data'] = $websiteData;
        return $websites;
    }//getWebsites function ends

    public function getCustomers($updatedAt = "",$limit = 2)
    {
        $updatedAt = strtotime($updatedAt);
        $updatedAt = date('Y-m-d H:i:s', $updatedAt); 
        $customers = Mage::getModel("customer/customer")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('id')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('updated_at')
            ->addAttributeToFilter('updated_at', array('gteq' =>$updatedAt));

        if($limit>1)
            $customers->getSelect()->limit($limit);

        $customerIds = array();
        foreach ($customers as $customer) {
            $customerIds[] = $customer->getId();
        }
        return $customerIds;
    }//getCustomers function ends

    public function getOrders($updatedAt = "",$limit = 2)
    {
        $updatedAt = strtotime($updatedAt);
        $updatedAt = date('Y-m-d H:i:s', $updatedAt); 
        $orders = Mage::getModel("sales/order")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('increment_id')
            ->addAttributeToSelect('updated_at')
            ->addAttributeToFilter('updated_at', array('gteq' =>$updatedAt));

        if($limit>1)
            $orders->getSelect()->limit($limit);

        $orderIds = array();
        foreach ($orders as $order) {
            $orderIds[] = $order->getIncrementId();
        }
        return $orderIds;
    }//getOrders function ends

    public function getAttributes()
    {
        $attributeSets = Mage::getModel('catalog/product_attribute_set_api')->items();

        $attributesData['attribute_sets']['total_attribute_sets'] = count($attributeSets);
        $attributesData['attribute_sets']['data'] = $attributeSets;
        $attributesData['attributes'] = array();
        $attributeSetCount = 0;

        foreach ($attributeSets as $attributeSet) {
            $attributeSetCount++;
            $items = Mage::getModel('catalog/product_attribute_api')->items($attributeSet['set_id']);
            $attributesData['attributes'][$attributeSetCount]['total_attributes'] = count($items);
            $attributesData['attributes'][$attributeSetCount]['attribute_set_id'] = $attributeSet['set_id'];
            $attributesData['attributes'][$attributeSetCount]['name'] = $attributeSet['name'];
            $attributesData['attributes'][$attributeSetCount]['data'] = $items;
        }//foreach attributeSet ends

        return $attributesData;
    }

    public function getProducts($updatedAt,$limit = 5,$storeId = null)
    {
        $updatedAt = strtotime($updatedAt);
        $updatedAt = date('Y-m-d H:i:s', $updatedAt); 
        $products = Mage::getModel("catalog/product")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('updated_at', array('gteq' =>$updatedAt))
            ->setStoreId($storeId);

        if($limit>1)
            $products->getSelect()->limit($limit);

        $productData = array();
        $p=0;
        foreach ($products as $product) {
            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $frontendInput = $attribute->getFrontendInput();

                if ($frontendInput == 'multiselect' or $frontendInput == 'select') {
                    $productData[$p][$attributeCode] = $product->getAttributeText($attributeCode);
                } else {
                    $productData[$p][$attributeCode] = $product->getData($attributeCode);
                }
            }//foreach attributes ends
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $productData[$p]['stock_data'] = $stock->getData();
            $productData[$p]['product_url'] = $product->getProductUrl();
            $productData[$p]['image_url'] = $product->getImage();
            $productData[$p]['store_ids'] = $product->getStoreIds();
            $p++;
        }
        return $productData;
    }//getProducts function ends
}