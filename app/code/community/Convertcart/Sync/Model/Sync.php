<?php
class Convertcart_Sync_Model_Sync extends Mage_Core_Model_Session_Abstract
{
    public $updatedAt;
    public $limit;
    public $offset;
    public $storeId;

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

    public function getBaseUrl($storeId)
    {
        $url['base_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK);
        $url['base_link_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $url['base_skin_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
        $url['base_media_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $url['base_js_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);        

        return $url;
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
                    $storeData[$storeCount]['url'] = $this->getBaseUrl($store->getId());
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

    public function getCustomers($params)
    {
        $this->setParams($params);
        $customers = Mage::getModel("customer/customer")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('id')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('updated_at')
            ->addAttributeToFilter('updated_at', array('gteq' =>$this->updatedAt));

        $page = $this->calculatePage();
        $customers->setPageSize($this->limit)->setCurPage($page);

        $customerIds = array();
        foreach ($customers as $customer) {
            $customerIds[] = $customer->getId();
        }

        return $customerIds;
    }//getCustomers function ends

    public function getOrders($params)
    {
        $this->setParams($params);
        $orders = Mage::getModel("sales/order")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('increment_id')
            ->addAttributeToSelect('updated_at')
            ->addAttributeToFilter('updated_at', array('gteq' =>$this->updatedAt));

        $page = $this->calculatePage();
        $orders->setPageSize($this->limit)->setCurPage($page);

        $orderIds = array();
        foreach ($orders as $order) {
            $orderIds[] = $order->getIncrementId();
        }
        return $orderIds;

    }//getOrders function ends

    public function getProducts($params)
    {
        $this->setParams($params);
        $page = $this->calculatePage();
        // $updatedAt = strtotime($updatedAt);
        // $updatedAt = date('Y-m-d H:i:s', $updatedAt); 

        $products = Mage::getModel("catalog/product")
            ->getCollection()
            ->addAttributeToSort('updated_at', 'desc')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('updated_at', array('gteq' =>$this->updatedAt));

        $products->setStoreId($this->storeId);
        $products->setPageSize($this->limit)->setCurPage($page);

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

    public function getCategories($params)
    {
        $this->setParams($params);        

        $rootid     = Mage::app()->getStore($this->storeid)->getRootCategoryId();
        $categories = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('path', array('like'=> "1/$rootid/%"));

        $categoryData = array();
        foreach ($categories as $category) {
            $categoryData[] = Mage::getModel('catalog/category_api')->info($category->getId());
        }

        return $categoryData;
    }// getCategories function ends

    public function calculatePage()
    {
        if ($this->offset == 0)
            $page = 1;
        else
            $page = number_format(floor($this->offset/$this->limit) + 1);

        return $page;
    }//calculatePage function ends

    public function setParams($params)
    {
        $this->updatedAt = isset($params['updatedAt']) ? str_ireplace("T", " ", $params['updatedAt']) : '2011-07-29 00:00:00';
        $this->limit = isset($params['limit']) ? $params['limit'] : 5;
        $this->offset = isset($params['offset']) ? $params['offset'] : 1;
        $this->storeId = isset($params['storeId']) ? $params['storeId'] : 1;
    }

    public function getParams()
    {
        $request = Mage::app()->getRequest();
        if ($request)
            $params = $request->getParams();
        else
            $params = null;

        return $params;        
    }
}