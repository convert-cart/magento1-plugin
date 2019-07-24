<?php
class Convertcart_Sync_Model_Cc extends Mage_Core_Model_Session_Abstract
{
    public $updatedAt;
    public $limit;
    public $offset;
    public $storeId;
    public $page;
    public $order;
    public $showRelatedProducts;
    public $debug = 0;
    public $subscriberId=0;
    public $queryMethod;
    public $customerEmailId;
    public $wishlistId;

    public function getProductData($product)
    {
        if (!is_object($product)) {
            return;
        }

        if ($this->queryMethod == 'custom') {
            $product->setStoreId($this->storeId);
            $attributes = $product->getAttributes();
            $productData['product_id'] = $product->getId();
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $frontendInput = $attribute->getFrontendInput();
                if ($frontendInput == 'multiselect' or $frontendInput == 'select') {
                    $productData[$attributeCode] = $product->getAttributeText($attributeCode);
                } else {
                    $productData[$attributeCode] = $product->getData($attributeCode);
                }
            }

            $productData['category_ids'] = $product->getCategoryIds();
            $productData['childProductIds'] = $this->getChildProductIds($product);
            $productData['final_price'] = $product->getFinalPrice();
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $productData['stock_data'] = $stock->getData();
            $productData['store_url'] = $product->getProductUrl();
            $productData['url'] = Mage::app()->getStore($this->storeId)
                                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).$product->getUrlPath();
            if ($product->getImage() != null and $product->getImage() != 'no_selection') {
                $productData['image_url'] = Mage::app()->getStore($this->storeId)
                                            ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
                $productData['image_url'].= 'catalog/product' . $product->getImage();
            }

            $productData['store_ids'] = $product->getStoreIds();
            $productData['priceRange'] = $this->getPriceRange($product);
        } elseif ($this->queryMethod == 'api') { //not reliable in some magento installs/environment
            // loading model again is not optimal approach,
            // but unable to get attribute in specific stores in a particular magento install/version/environment
            $productData = Mage::getModel('catalog/product_api')->info($product->getId(), $this->storeId);
        }

        $productData['isSalable'] = $this->isSaleable($product);
        $productData['configInfo'] = $this->getConfigInfo($product);
        if ($this->showRelatedProducts != 0) {
            $productData['relatedProductIds'] = $product->getRelatedProductIds();
            $productData['crossSellProductIds'] = $product->getCrossSellProductIds();
            $productData['upSellProductIds'] = $product->getUpSellProductIds();
        }

        $productData['parentProductIds'] = $this->getParentProductIds($product);
        $productData['baseImageUrl'] = Mage::getModel('catalog/product_media_config')
              ->getMediaUrl($product->getImage());
        $productData['smallImageUrl'] = Mage::getModel('catalog/product_media_config')
              ->getMediaUrl($product->getSmallImage());
        $productData['thumbnailImageUrl'] = Mage::getModel('catalog/product_media_config')
                   ->getMediaUrl($product->getThumbnail());
        $productData['allImages'] = $this->getMediaGallaryImage($product);

        return $productData;
    }

    public function getPriceRange($product)
    {
        $priceRange = array();
        if (!is_object($product)) {
            return $priceRange;
        }

        if ($product->getTypeId() == "bundle") {
            $priceModel  = $product->getPriceModel();
            if (is_object($priceModel)) {
                try {
                    $pricelist = $priceModel->getTotalPrices($product, null, null, false);
                    if (isset($pricelist[0])) $priceRange['lowPrice'] = $pricelist[0];
                    if (isset($pricelist[1])) $priceRange['highPrice'] = $pricelist[1];
                } catch (Exception $e) {
                }
            }
        }

        return $priceRange;
    }

    public function isSaleable($product)
    {
        if (!is_object($product)) {
            return null;
        }

        try {
            return $product->isSalable();
        } catch(Exception $e) {
            return null;
        }
    }

    public function getMediaGallaryImage($product)
    {
        $galleryImages = array();
        if (!is_object($product)) {
            return $galleryImages;
        }

        $product->load('media_gallery');//load media gallery attributes
        $mediaGallery = $product->getMediaGalleryImages();

        if (!is_object($mediaGallery)) {
            return $galleryImages;
        }

        foreach ($mediaGallery as $image) {
            $galleryImage['url'] = $image->getUrl();
            $galleryImage['id'] = $image->getId();
            $galleryImage['position'] = $image->getPosition();
            $galleryImage['label'] = $image->getLabel();
            $galleryImage['disabled'] = $image->getDisabled();
            $galleryImages[] = $galleryImage;
        }

        return $galleryImages;
    }

    public function getCategoryData($category)
    {
        $categoryData = array();
        if (!is_object($category)) {
            return $categoryData;
        }

        if ($this->queryMethod == 'custom') {
            $category->setStoreId($this->storeId);
            $categoryData['category_id'] = $category->getId();
            $categoryData['name'] = $category->getData('name');
            $categoryData['description'] = $category->getData('description');
            $categoryData['url_key'] = $category->getData('url_key');
            $categoryData['url'] = Mage::app()
                                  ->getStore($this->storeId)
                                  ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK);
            $categoryData['url'].= $category->getData('url_path');
            $categoryData['image'] = $category->getData('image');
            $categoryData['meta_title'] = $category->getData('meta_title');
            $categoryData['meta_keywords'] = $category->getData('meta_keywords');
            $categoryData['meta_description'] = $category->getData('meta_description');
            $categoryData['is_active'] = $category->getData('is_active');
            $categoryData['position'] = $category->getData('position');
            $categoryData['level'] = $category->getData('level');
            $categoryData['parent_id'] = $category->getData('parent_id');
            $categoryData['path'] = $category->getData('path');
            $categoryData['include_in_menu'] = $category->getData('include_in_menu');
            $categoryData['created_at'] = $category->getData('created_at');
            $categoryData['updated_at'] = $category->getData('updated_at');
        } elseif ($this->queryMethod == 'api') { //not reliable in some magento installs/environment
            $categoryData = Mage::getModel('catalog/category_api')->info($category->getId(), $this->storeId);
        }

        return $categoryData;
    }

    public function getConfigInfo($product)
    {
        if (!is_object($product)) {
            return null;
        }

        if ($product->getTypeId() != "configurable") {
            return null;
        }

        $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        $configArray = array();
        $configArray['basePrice'] = $product->getFinalPrice();
        $configArray['options'] = array();
        foreach ($attributes as $attribute) {
            $configArray['options'] = array_merge($configArray['options'], $attribute->getPrices());
        }

        $configArray['children'] = array();
        $simpleProducts = $product->getTypeInstance()->getUsedProducts();
        foreach ($simpleProducts as $simpleProduct) {
            $childInfo = array();
            foreach ($attributes as $attribute) {
                $childInfo['product_id'] = $simpleProduct->getId();
                $childInfo['product_sku'] = $simpleProduct->getSku();
                $childInfo['attribute_code'] = $attribute->getProductAttribute()->getAttributeCode();
                $childInfo['attribute_value'] = $simpleProduct->getData($childInfo['attribute_code']);
                $childInfo['value_id'] = $childInfo['attribute_value'];
            }

            $configArray['children'][] = $childInfo;
        }

        return $configArray;
    }

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

        $groupParentIds = Mage::getModel('catalog/product_type_grouped')
                         ->getParentIdsByChild($childProduct->getId());
        $configParentIds = Mage::getModel('catalog/product_type_configurable')
                         ->getParentIdsByChild($childProduct->getId());
        $bundleParentIds = Mage::getModel('bundle/product_type')
                         ->getParentIdsByChild($childProduct->getId());
        $parentIds = array_merge($groupParentIds, $configParentIds);
        $parentIds = array_merge($parentIds, $bundleParentIds);
        return $parentProductIds;
    }

    public function getReviewDetails($review)
    {
        $reviewDetails = array();
        if (!is_object($review)) {
            return $reviewDetails;
        }

        $ratingOb = Mage::getModel('rating/rating')
                  ->getEntitySummary($review->getEntitPkValue());
        if (is_object($ratingOb)) {
            $reviewDetails['rating'] = $ratingOb->getSum()/$ratingOb->getCount();
        }


        $reviewDetails['id'] = $review->getId();
        $reviewDetails['product_id'] = $review->getEntityPkValue();
        if ($review->getCustomerId() == null) {
            $reviewDetails['customer_id'] = 'guest';
        } else {
            $reviewDetails['customer_id'] = $review->getCustomerId();
        }

        $reviewDetails['review'] = $review->getDetail();
        $reviewDetails['title'] = $review->getTitle();
        $reviewDetails['createdAt'] = $review->getCreatedAt();
        $reviewDetails['statusId'] = $review->getStatusId();
        if ($review->getStatusId() == 1) {
            $reviewDetails['status'] = 'approved';
        } else if ($review->getStatusId() == 2) {
            $reviewDetails['status'] = 'pending';
        } else if ($review->getStatusId() == 3) {
            $reviewDetails['status'] = 'rejected';
        } else {
            $reviewDetails['status'] = 'other';
        }

        return $reviewDetails;
    }

    public function getAmWishlist($amList)
    {
        $wishlist = array();
        $ccHelper = Mage::Helper('convertcart_sync');
        $wishlist['id'] = $ccHelper->getArrValue($amList, 'list_id');
        $wishlist['name'] = $ccHelper->getArrValue($amList, 'title');
        $wishlist['customerId'] = $ccHelper->getArrValue($amList, 'customer_id');
        $wishlist['isDefault'] = $ccHelper->getArrValue($amList, 'is_default');
        $wishlist['createdAt'] = $ccHelper->getArrValue($amList, 'created_at');
        $amItemModel = Mage::getModel('amlist/item');
        if(!is_object($amItemModel)) return $wishlist;
        $amItems = $amItemModel->getCollection()
                     ->addFieldToFilter('list_id', $amList['list_id']);
        $wishlist['items'] = array();
        foreach ($amItems as $amItem) {
            $resource = Mage::getSingleton('catalog/product');
            $productId = $amItem->getProductId();
            if (is_object($resource)) {
                $resource = $resource->getResource();
                $item = array();
                $item['itemId'] = $ccHelper->getArrValue($amItem, 'item_id');
                $item['productId'] = $ccHelper->getArrValue($amItem, 'product_id');
                $item['qty'] = $ccHelper->getArrValue($amItem, 'qty');
                $item['sku'] = $resource->getAttributeRawValue($productId, "sku", $this->storeId);
                $item['url'] = Mage::helper('catalog/product')->getProductUrl($productId);
                $wishlist['items'][] = $item;
            }
        }

        return $wishlist;
    }

    public function debugMode()
    {
        if ($this->debug == 1) {
            error_reporting(E_ALL);
            Mage::setIsDeveloperMode(true);
        }
    }

    public function calculatePage()
    {
        if ($this->offset == 0) {
            $this->page = 1;
        } else {
            $this->page = number_format(floor($this->offset/$this->limit) + 1);
        }
    }

    public function setParams($params)
    {
        $this->updatedAt = isset($params['updatedAt']) ? str_ireplace("T", " ", $params['updatedAt']) : '2011-07-29 00:00:00';
        $this->limit = isset($params['limit']) ? $params['limit'] : 5;
        $this->offset = isset($params['offset']) ? $params['offset'] : 0;
        $this->order = isset($params['order']) ? $params['order'] : 'asc';
        $this->storeId = isset($params['storeId']) ? $params['storeId'] : 1;
        $this->debug = isset($params['debug']) ? $params['debug'] : 0;
        $this->productFlatDisabled = isset($params['productFlatDisabled']) ? $params['productFlatDisabled'] : 0;
        $this->subscriberId = isset($params['subscriberId']) ? $params['subscriberId'] : 0;
        $this->showRelatedProducts = isset($params['showRelatedProducts']) ? $params['showRelatedProducts'] : 1;
        $this->queryMethod = isset($params['queryMethod']) ? $params['queryMethod'] : 'custom';
        $this->customerEmailId = isset($params['customerEmailId']) ? $params['customerEmailId'] : 0;
        $this->wishlistId = isset($params['wishlistId']) ? $params['wishlistId'] : 0;
        $this->debugMode();
        $this->calculatePage();
    }

    public function getParams()
    {
        $request = Mage::app()->getRequest();
        if ($request) {
            $params = $request->getParams();
        } else {
            $params = null;
        }

        return $params;
    }
}
