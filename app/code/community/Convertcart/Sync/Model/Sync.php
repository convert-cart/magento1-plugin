<?php
class Convertcart_Sync_Model_Sync extends Mage_Core_Model_Session_Abstract
{
    public $updatedAt;
    public $limit;
    public $offset;
    public $storeId;
    public $page;
    public $order;
    public $debug = 0;
    public $subscriberId=0;

    public function getCountData()
    {
        $this->setParams($params);        
        $countData = array();

        $countData['websites']  = $this->getWebsitesData();
        $countData['products']  = $this->getProductCount();
        $countData['customers'] = $this->getCustomerCount();
        $countData['orders'] = $this->getOrderCount();

        return $countData;
    }

    public function getCustomerCount()
    {
        $collection = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSelect('id');

        $customers['total_customers'] = $collection->getSize();
        return $customers;
    }

    public function getOrderCount()
    {
        $collection = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addAttributeToSelect('entity_id');

        $orders['total_orders'] = $collection->getSize();

        return $orders;
    }

    public function getProductCount()
    {
        $collection = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect('entity_id');

        $products['total_products'] = $collection->getSize();
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
            $websiteData['website_id'] = $website->getId();
            $websiteData['website_code'] = $website->getCode();
            $websiteData['website_name'] = $website->getName();

            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeCount++;
                    $storeData['store_id'] = $store->getId();
                    $storeData['store_code'] = $store->getCode();
                    $storeData['store_name'] = $store->getName();                    

                    $allowedCurrencies = $store->getAvailableCurrencyCodes(true);
                    $storeData['base_currency'] = $store->getBaseCurrencyCode();
                    if (is_array($allowedCurrencies) && count($allowedCurrencies) > 1) {
                        $storeData['allowed_currencies'] = Mage::getModel('directory/currency')->getCurrencyRates(
                            $store->getBaseCurrencyCode(),
                            $allowedCurrencies
                        );
                    } else
                        $storeData['allowed_currencies'] = $allowedCurrencies;
                    $storeData['url'] = $this->getBaseUrl($store->getId());
                    $allStore[] = $storeData;
                }
            }
            $websiteData['total_stores'] = $storeCount;
            $websiteData['stores'] = $allStore; 
            $allWebsite[] =  $websiteData;
        }

        $wesbites = array();
        $websites['total_websites'] = $websiteCount;
        $websites['data'] = $allWebsite;

        return $websites;
    }//getWebsites function ends

    public function getCurrencyInfo()
    {
        $currencyModel = Mage::getModel('directory/currency');
        if(!is_object($currencyModel))
            return;

        $currencies = $currencyModel->getConfigAllowCurrencies();
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $defaultCurrencies = $currencyModel->getConfigBaseCurrencies();         
        $rates = $currencyModel->getCurrencyRates($defaultCurrencies, $currencies);

        $currencyData = array();
        $currencyData['base_currency'] = $baseCurrencyCode;
        foreach ($rates[$baseCurrencyCode] as $key=>$value  ) {
            $currencyData['rate'][$key] = $value;
        }

        return $currencyData;
    }//getCurrencyInfo function ends

    public function getAttributes()
    {
        $this->setParams($params);        
        $attributeSets = Mage::getModel('catalog/product_attribute_set_api')->items();
        $attributesData['total_attribute_sets'] = count($attributeSets);

        $attributesData['attribute_set'] = array();
        foreach ($attributeSets as $attributeSet) {
            $items = Mage::getModel('catalog/product_attribute_api')->items($attributeSet['set_id']);
            $attributeData['total_attributes'] = count($items);
            $attributeData['attribute_set_id'] = $attributeSet['set_id'];
            $attributeData['name'] = $attributeSet['name'];
            $attributeData['data'] = $items;

            $attributesData['attribute_set'][] = $attributeData;

        }//foreach attributeSet ends

        return $attributesData;
    }//getAttributes function ends

    public function getCustomers($params)
    {
        $this->setParams($params);
        $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSort('updated_at', $this->order)
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
        $orders = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSort('updated_at', $this->order)
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

        $products = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->setStoreId($this->storeId)
                    ->addAttributeToSort('updated_at', $this->order)
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('updated_at', array('gteq' => $this->updatedAt));

        $products = $products
                    ->setPageSize($this->limit)
                    ->setCurPage($this->page);

        $productData = array();
        $p=0;
        foreach ($products as $product) {
            // loading model again is not optimal approach, but unable to get attribute in specific stores in a magento install
            // $product = Mage::getModel('catalog/product')
            //             ->setStoreId($this->storeId)
            //             ->load($_product->getId());
            // $productData[$p] = Mage::getModel('catalog/product_api')->info($product->getId(), $this->storeId);

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
            $productData[$p]['store_url'] = $product->getProductUrl();
            $productData[$p]['url'] = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$product->getUrlPath();
            $productData[$p]['image_url'] = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
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
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('path', array('like'=> "1/$rootid%"));

        $categories = $categories
                    ->addAttributeToSort('updated_at', $this->order)
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
            $cat['url']         = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$category->getData('url_path');
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
                            ->setOrder('updated_at', $this->order);

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

    public function getNewsletterSubscribers($params)
    {
        $this->setParams($params);

        $collection = Mage::getModel('newsletter/subscriber')
                    ->getCollection()
                    ->addFieldToSelect('subscriber_id')
                    ->addFieldToSelect('store_id')
                    ->addFieldToSelect('customer_id')
                    ->addFieldToSelect('subscriber_email')
                    ->addFieldToSelect('subscriber_status')
                    ->addFieldToFilter('subscriber_id', array('gteq' => $this->subscriberId))
                    ->setOrder('subscriber_id', $this->order);

        $collection = $collection
                    ->setPageSize($this->limit)
                    ->setCurPage($this->page);

        $newsletterSubscribers = array();
        foreach ($collection as $subscriber) {
            $newsletterSubscriber['subscriber_id'] = $subscriber['subscriber_id'];
            $newsletterSubscriber['store_id'] = $subscriber['store_id'];
            $newsletterSubscriber['customer_id'] = $subscriber['customer_id'];
            $newsletterSubscriber['subscriber_email'] = $subscriber['subscriber_email'];
            $newsletterSubscriber['subscriber_status'] = $subscriber['subscriber_status'];

            $newsletterSubscribers[] = $newsletterSubscriber;
        }
        return $newsletterSubscribers;
    }//getNewsletterSubscribers function ends

    public function calculatePage()
    {
        if ($this->offset == 0)
            $this->page = 1;
        else
            $this->page = number_format(floor($this->offset/$this->limit) + 1);
    }//calculatePage function ends

    public function debugMode()
    {
        if ($this->debug == 1) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            Mage::setIsDeveloperMode(true);
        }
    }

    public function setParams($params)
    {
        $this->updatedAt = isset($params['updatedAt']) ? str_ireplace("T", " ", $params['updatedAt']) : '2011-07-29 00:00:00';
        // $this->updatedAt = isset($params['updatedAt']) ? date("Y-m-d h:i:s", $params['updatedAt']/1000) : '2011-07-29 00:00:00';
        $this->limit = isset($params['limit']) ? $params['limit'] : 5;
        $this->offset = isset($params['offset']) ? $params['offset'] : 0;
        $this->order = isset($params['order']) ? $params['order'] : 'asc';        
        $this->storeId = isset($params['storeId']) ? $params['storeId'] : 0;
        $this->debug = isset($params['debug']) ? $params['debug'] : 0;
        $this->subscriberId = isset($params['subscriberId']) ? $params['subscriberId'] : 0;

        $this->debugMode();
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