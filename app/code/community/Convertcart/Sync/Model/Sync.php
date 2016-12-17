<?php
class Convertcart_Sync_Model_Sync extends Mage_Core_Model_Session_Abstract
{
    public $updatedAt;
    public $limit;
    public $offset;
    public $storeId;
    public $page;

    public function getCountData()
    {
        $countData = array();

        $countData['websites']  = $this->getWebsitesData();
        $countData['products']  = $this->getProductCount();
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
        $customers['last_id']  = $collection->getLastItem()->getId();

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
            $websiteData[$websiteCount]['website_id'] = $website->getId();
            $websiteData[$websiteCount]['website_code'] = $website->getCode();
            $websiteData[$websiteCount]['website_name'] = $website->getName();

            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeCount++;
                    $storeData[$storeCount]['store_id'] = $store->getId();
                    $storeData[$storeCount]['store_code'] = $store->getCode();
                    $storeData[$storeCount]['store_name'] = $store->getName();                    
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

        $customers = $customers
                    ->setPageSize($this->limit)
                    ->setCurPage($this->page);

        $c=0;
        $customerData = array();
        foreach ($customers as $customer) {
            $customerData[$c] = Mage::getModel('customer/customer_api')->info($customer->getId());
            //we dont need hash, dont send these fields
            unset($customerData[$c]['password_hash']);
            unset($customerData[$c]['rp_token']);
            unset($customerData[$c]['rp_token_created_at']);
            unset($customerData[$c]['confirmation']);
            unset($customerData[$c]['disable_auto_group_change']);
            unset($customerData[$c]['reward_update_notification']);
            unset($customerData[$c]['reward_warning_notification']);
            $c++;
        }

        return $customerData;
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

        $orders = $orders
                ->setPageSize($this->limit)
                ->setCurPage($this->page);

        $orderData = array();
        foreach ($orders as $order) {
            $orderData[] = Mage::getModel('sales/order_api')->info($order->getIncrementId());
        }

        return $orderData;
    }//getOrders function ends

    public function getProducts($params)
    {
        $this->setParams($params);

        $products = Mage::getModel("catalog/product")
                    ->getCollection()
                    ->addAttributeToSort('updated_at', 'desc')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('updated_at', array('gteq' => $this->updatedAt));

        $products = $products
                    ->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->setStoreId($this->storeId);

        $productData = array();
        $p=0;
        $prodResource = Mage::getSingleton('catalog/product')->getResource();

        foreach ($products as $product) {
            $productData[$p] = Mage::getModel('catalog/product_api')->info($product->getId(), $this->storeId);
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $productData[$p]['stock_data'] = $stock->getData();
            $productData[$p]['url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$productData[$p]['url_path'];
            $productData[$p]['image_url'] = $prodResource->getAttributeRawValue($product->getId(), "image", $this->storeId);
            $productData[$p]['store_ids'] = $product->getStoreIds();
            $p++;
        }
        return $productData;
    }//getProducts function ends


    public function getCategories($params)
    {
        $this->setParams($params);
        $rootid     = Mage::app()->getStore($this->storeId)->getRootCategoryId();
        $categories = Mage::getModel('convertcart_sync/category')
                    ->load($rootid)
                    ->getCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('entity_id')
                    ->addFieldToFilter('path', array('like'=> "1/$rootid%"));

        $categories = $categories
                    ->addAttributeToSort('updated_at', 'desc')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('updated_at', array('gteq' =>$this->updatedAt))
                    ->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->setStoreId($this->storeId);

        $categoryData = array();
        foreach ($categories as $category) {            
            $cat = array();
            $cat['category_id'] = $category->getId();
            $cat['name']        = $category->getData('name');
            $cat['description'] = $category->getData('description');
            $cat['url_key']     = $category->getData('url_key');
            $cat['url']     = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$category->getData('url_path');
            $cat['image']       = $category->getData('image');

            $cat['meta_title']       = $category->getData('meta_title');
            $cat['meta_keywords']    = $category->getData('meta_keywords');
            $cat['meta_description'] = $category->getData('meta_description');

            $cat['is_active']   = $category->getData('is_active');
            $cat['position']    = $category->getData('position');
            $cat['level']       = $category->getData('level');
            $cat['parent_id']   = $category->getData('parent_id');
            $cat['path']        = $category->getData('path');
            $cat['include_in_menu'] = $category->getData('include_in_menu');

            $cat['created_at']  = $category->getData('created_at');
            $cat['updated_at']  = $category->getData('updated_at');

            $categoryData[] = $cat;
        }
        return $categoryData;
    }// getCategories function ends

    public function getWishlist($params)
    {
        $this->setParams($params);
        
        $wishlistCollection = Mage::getModel("wishlist/wishlist")
                            ->getCollection()
                            ->addFieldToFilter('updated_at', array('gteq' =>$this->updatedAt))
                            ->setOrder('updated_at', 'desc');

        $wishlistCollection = $wishlistCollection
                            ->setPageSize($this->limit)
                            ->setCurPage($this->page);

        $wishlistData = array();
        $i=0;

        foreach ($wishlistCollection as $wishlist) {
            $wishlistData[$i]['wishlist_id'] = $wishlist->getWishlistId();
            $wishlistData[$i]['customer_id'] = $wishlist->getCustomerId();
            $wishlistData[$i]['updated_at'] = $wishlist->getUpdatedAt();
            $wishlistData[$i]['items'] = array();

            $wishListItemCollection = $wishlist->getItemCollection();
            if (count($wishListItemCollection)) {
                foreach ($wishListItemCollection as $item) {
                    $wishlistItem = array();
                    $wishlistItem['product_id'] = $item->getProductId();
                    $wishlistItem['name'] = $item->getProductName();
                    $resource = Mage::getSingleton('catalog/product');
                    if (is_object($resource) and $item->getStoreId() != null) {
                        $resource = $resource->getResource();
                        $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $item->getStoreId());
                        $wishlistItem['sku'] = $resource->getAttributeRawValue($item->getProductId(), "sku", $item->getStoreId());
                        if($imagePath != null and $imagePath != "no_selection")
                            $wishlistItem['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
                    }
                    $wishlistItem['url'] = $item->getProduct()->getProductUrl();
                    $wishlistItem['qty'] = $item->getData('qty');
                    $wishlistItem['added_at'] = $item->getData('added_at');
                    $wishlistItem['store_id'] = $item->getStoreId();
                    $wishlistData[$i]['items'] = $wishlistItem;
                }
            }
            $i++;
        }
        return $wishlistData;
    } //getWishlist function ends

    public function calculatePage()
    {
        if ($this->offset == 0)
            $this->page = 1;
        else
            $this->page = number_format(floor($this->offset/$this->limit) + 1);
    }//calculatePage function ends

    public function setParams($params)
    {
        $this->updatedAt = isset($params['updatedAt']) ? str_ireplace("T", " ", $params['updatedAt']) : '2011-07-29 00:00:00';
        // $this->updatedAt = isset($params['updatedAt']) ? date("Y-m-d h:i:s", $params['updatedAt']/1000) : '2011-07-29 00:00:00';
        $this->limit = isset($params['limit']) ? $params['limit'] : 5;
        $this->offset = isset($params['offset']) ? $params['offset'] : 1;
        $this->storeId = isset($params['storeId']) ? $params['storeId'] : 1;
        $this->calculatePage();
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