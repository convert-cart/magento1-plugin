<?php
class Convertcart_Sync_Model_Find extends Mage_Core_Model_Session_Abstract
{
    public $debugMode;
    public $storeId;

    public function getCustomer($params)
    {
        if (!isset($params['id']) and !isset($params['email'])) {
            return null;
        }

        $this->setParams($params);
        if (isset($params['id'])) {
            $customerData = Mage::getModel('customer/customer')->load($params['id'])
                            ->getData();
        } elseif (isset($params['email'])) {
            $websiteId = Mage::getModel('core/store')->load($this->storeId)->getWebsiteId();
            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($params['email']);
            $customerData = $customer->getData();
        }
        if (!is_array($customerData)) {
            return null;
        }

        //we dont need hash, dont send these fields
        unset($customerData['password_hash']);
        unset($customerData['rp_token']);
        unset($customerData['rp_token_created_at']);
        unset($customerData['confirmation']);
        unset($customerData['disable_auto_group_change']);
        unset($customerData['reward_update_notificategoryDataion']);
        unset($customerData['reward_warning_notificategoryDataion']);

        return $customerData;
    }//getCustomer function ends

    public function getOrder($params)
    {
        if (!isset($params['id'])) { //expecting increment_id of order
            return null;
        }

        $this->setParams($params);
        $orderData = Mage::getModel('sales/order_api')->info($params['id']);

        return $orderData;
    }//getOrder function ends

    public function getProduct($params)
    {
        if (!isset($params['id']) and !isset($params['sku'])) {
            return null;
        }

        $this->setParams($params);
        if (isset($params['id'])) {
            $product = Mage::getModel('catalog/product')
                        ->load($params['id']);
        } elseif (isset($params['sku'])) {
            $product = Mage::getModel('catalog/product')
                        ->loadByAttribute('sku', $params['sku']);
        }
        if (!is_object($product)) {
            return null;
        }

        $product->setStoreId($this->storeId);

        $productData = array();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $frontendInput = $attribute->getFrontendInput();
            if ($frontendInput == 'multiselect' or $frontendInput == 'select') {
                $productData[$attributeCode] = $product->getAttributeText($attributeCode);
            } else {
                $productData[$attributeCode] = $product->getData($attributeCode);
            }
        }//foreach attributes ends

        $productData['category_ids'] = $product->getCategoryIds();
        $productData['childProductIds'] = $this->getChildProductIds($product);

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $productData['stock_data'] = $stock->getData();
        $productData['store_url'] = $product->getProductUrl();
        $productData['url'] = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$product->getUrlPath();
        $productData['image_url'] = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        $productData['store_ids'] = $product->getStoreIds();

        return $productData;
    }//getProduct function ends

    public function getCategory($params)
    {
        if (!isset($params['id'])) {
            return null;
        }

        $this->setParams($params);
        $category = Mage::getModel('convertcart_sync/category')
                    ->load($params['id']);

        if (!is_object($category)) {
            return null;
        }
        $category->setStoreId($this->storeId);
        $categoryData = array();
        $categoryData['category_id'] = $category->getId();
        $categoryData['name']        = $category->getData('name');
        $categoryData['description'] = $category->getData('description');
        $categoryData['url_key']     = $category->getData('url_key');
        $categoryData['url']         = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$category->getData('url_path');
        $categoryData['image']       = $category->getData('image');

        $categoryData['meta_title']       = $category->getData('meta_title');
        $categoryData['meta_keywords']    = $category->getData('meta_keywords');
        $categoryData['meta_description'] = $category->getData('meta_description');

        $categoryData['is_active']   = $category->getData('is_active');
        $categoryData['position']    = $category->getData('position');
        $categoryData['level']       = $category->getData('level');
        $categoryData['parent_id']   = $category->getData('parent_id');
        $categoryData['path']        = $category->getData('path');
        $categoryData['include_in_menu'] = $category->getData('include_in_menu');

        $categoryData['created_at']  = $category->getData('created_at');
        $categoryData['updated_at']  = $category->getData('updated_at');

        return $categoryData;
    }// getCategory function ends

    public function getChildProductIds($parentProduct)
    {
        $childProductIds = array();
        if (!is_object($parentProduct)) {
            return $childProductIds;
        }

        $productTypeId = $parentProduct->getTypeId();
        if (($productTypeId == "grouped" or $productTypeId == "bundle") or $productTypeId == "configurable") {
            $ids = $parentProduct->getTypeInstance()
                    ->getChildrenIds($parentProduct->getId());
            foreach ($ids as $optionId => $children) {
                foreach ($children as $id => $childId) {
                    $childProductIds[$optionId][] = $childId;
                }
            }
        }

        return $childProductIds;
    }

    public function getParentProductIds($childProduct)
    {
        $parentProductIds = array();
        if (!is_object($childProduct)) {
            return $parentProductIds;
        }

        if ($childProduct->getTypeId() == "simple") {
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($childProduct->getId());
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($childProduct->getId());
                if (!$parentIds) {
                    $parentIds = Mage::getModel('bundle/product_type')->getParentIdsByChild($childProduct->getId());
                }
            }
            if (isset($parentIds[0])) {
                $parentProductIds = $parentIds;
            }
        }

        return $parentProductIds;
    }


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
        $this->storeId = isset($params['storeId']) ? $params['storeId'] : 0;
        $this->debug = isset($params['debug']) ? $params['debug'] : 0;
        $this->debugMode();
    }
}