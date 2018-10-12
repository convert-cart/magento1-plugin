<?php
class Convertcart_Sync_Model_Sync extends Mage_Core_Model_Session_Abstract
{
    public function getStoreInfo()
    {
        $storeInfo = array();
        $storeInfo['websites']  = $this->getWebsitesData();
        $storeInfo['products']  = $this->getProductCount();
        $storeInfo['category']  = $this->getCategoryCount();
        $storeInfo['customers'] = $this->getCustomerCount();
        $storeInfo['orders'] = $this->getOrderCount();
        $storeInfo['customerConfig'] = Mage::getStoreConfig('customer/account_share/scope');
        $storeInfo['moduleVersion'] = Mage::Helper('convertcart_sync')->getModuleVersion();

        return $storeInfo;
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

    public function getCategoryCount()
    {
        $collection = Mage::getModel('convertcart_sync/category')
                    ->getCollection()
                    ->addAttributeToSelect('entity_id');

        $categories['total_categories'] = $collection->getSize();
        return $categories;
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
            $allStore = array();

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
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);
        $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSort('updated_at', $ccModel->order)
                    ->addAttributeToSelect('id')
                    ->addAttributeToSelect('email')
                    ->addAttributeToSelect('updated_at')
                    ->addAttributeToFilter('updated_at', array('gteq' =>$ccModel->updatedAt));

        $customers = $customers
                    ->setPageSize($ccModel->limit)
                    ->setCurPage($ccModel->page);

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
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $orders = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSort('updated_at', $ccModel->order)
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('updated_at')
                ->addAttributeToFilter('updated_at', array('gteq' =>$ccModel->updatedAt));

        $orders = $orders
                ->setPageSize($ccModel->limit)
                ->setCurPage($ccModel->page);

        $orderData = array();
        foreach ($orders as $order) {
            $orderData[] = Mage::getModel('sales/order_api')->info($order->getIncrementId());
        }

        return $orderData;
    }//getOrders function ends

    public function getProducts($params)
    {
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $products = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->setStoreId($ccModel->storeId)
                    ->addAttributeToSort('updated_at', $ccModel->order)
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('updated_at', array('gteq' => $ccModel->updatedAt));

        $products = $products
                    ->setPageSize($ccModel->limit)
                    ->setCurPage($ccModel->page);

        $productData = array();
        $p=0;
        foreach ($products as $product) {
            $productData[$p] = $ccModel->getProductData($product);
            $p++;
        }
        return $productData;
    }//getProducts function ends

    public function getProductsCustomAttr($params)
    {
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);
        $requiredAttr = !empty($params['requiredAttr']) ? $params['requiredAttr'] : array('price', 'name');
        $products = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->setStoreId($ccModel->storeId)
                    ->addAttributeToSort('updated_at', $ccModel->order);

        foreach ($requiredAttr as $attr) {
            $products = $products
                ->addAttributeToSelect($attr);
        }

        $products = $products
                    ->addAttributeToFilter('updated_at', array('gteq' => $ccModel->updatedAt))
                    ->setPageSize($ccModel->limit)
                    ->setCurPage($ccModel->page);

        $productData = array();
        foreach ($products as $product) {
            $prod = array();
            $prod['final_price'] = $product->getFinalPrice();
            foreach ($requiredAttr as $attr) {
                $prod[$attr] = $product->getData($attr);
            }
            $productData[] = $prod;
        }
        return $productData;
    }

    public function getCategories($params)
    {
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $rootid     = Mage::app()->getStore($ccModel->storeId)->getRootCategoryId();
        $categories = Mage::getModel('convertcart_sync/category')
                    ->load($rootid)
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('path', array('like'=> "1/$rootid%"));

        $categories = $categories
                    ->addAttributeToSort('updated_at', $ccModel->order)
                    ->addAttributeToFilter('updated_at', array('gteq' =>$ccModel->updatedAt))
                    ->setPageSize($ccModel->limit)
                    ->setCurPage($ccModel->page)
                    ->setStoreId($ccModel->storeId);

        $categoryData = array();
        foreach ($categories as $category) {
            $categoryData[] = $ccModel->getCategoryData($category);
        }

        return $categoryData;
    }// getCategories function ends

    public function getWishlist($params)
    {
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $wishlistCollection = Mage::getModel("wishlist/wishlist")
                            ->getCollection()
                            ->addFieldToFilter('updated_at', array('gteq' =>$ccModel->updatedAt))
                            ->setOrder('updated_at', $ccModel->order);

        $wishlistCollection = $wishlistCollection
                            ->setPageSize($ccModel->limit)
                            ->setCurPage($ccModel->page);

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
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $collection = Mage::getModel('newsletter/subscriber')
                    ->getCollection()
                    ->addFieldToSelect('subscriber_id')
                    ->addFieldToSelect('store_id')
                    ->addFieldToSelect('customer_id')
                    ->addFieldToSelect('subscriber_email')
                    ->addFieldToSelect('subscriber_status')
                    ->addFieldToFilter('subscriber_id', array('gteq' => $ccModel->subscriberId))
                    ->setOrder('subscriber_id', $ccModel->order);

        $collection = $collection
                    ->setPageSize($ccModel->limit)
                    ->setCurPage($ccModel->page);

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
}