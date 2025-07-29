<?php
class Convertcart_Model_Sync_Cc extends Mage_Core_Model_Session_Abstract
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

    /**
     * Get product data based on query method
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductData($product)
    {
        if (!is_object($product)) {
            return [];
        }

        // Get base product data based on query method
        $productData = $this->getBaseProductData($product);
        if (empty($productData)) {
            return [];
        }

        // Add additional product information
        $this->addProductRelatedData($product, $productData);
        $this->addProductMediaData($product, $productData);
        $this->addProductRelations($product, $productData);

        return $productData;
    }

    /**
     * Get base product data based on query method
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function getBaseProductData($product)
    {
        if ($this->queryMethod === 'api') {
            return Mage::getModel('catalog/product_api')->info($product->getId(), $this->storeId);
        }
        
        return $this->getCustomProductData($product);
    }

    /**
     * Get product data using custom query method
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function getCustomProductData($product)
    {
        $product->setStoreId($this->storeId);
        $productData = [
            'product_id' => $product->getId(),
            'category_ids' => $product->getCategoryIds(),
            'childProductIds' => $this->getChildProductIds($product),
            'final_price' => $product->getFinalPrice(),
            'store_url' => $product->getProductUrl(),
            'url' => Mage::app()->getStore($this->storeId)
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK) . $product->getUrlPath(),
            'store_ids' => $product->getStoreIds(),
            'priceRange' => $this->getPriceRange($product)
        ];

        // Add stock data
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $productData['stock_data'] = $stock->getData();

        // Add product attributes
        $this->addProductAttributes($product, $productData);

        // Add image URL if available
        $this->addProductImageUrl($product, $productData);

        return $productData;
    }

    /**
     * Add product attributes to product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addProductAttributes($product, &$productData)
    {
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $frontendInput = $attribute->getFrontendInput();
            $productData[$attributeCode] = in_array($frontendInput, ['multiselect', 'select'])
                ? $product->getAttributeText($attributeCode)
                : $product->getData($attributeCode);
        }
    }

    /**
     * Add product image URL to product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addProductImageUrl($product, &$productData)
    {
        $image = $product->getImage();
        if (!empty($image) && $image !== 'no_selection') {
            $productData['image_url'] = Mage::app()->getStore($this->storeId)
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                . 'catalog/product' . $image;
        }
    }

    /**
     * Add product related data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addProductRelatedData($product, &$productData)
    {
        $productData['isSalable'] = $this->isSaleable($product);
        $productData['configInfo'] = $this->getConfigInfo($product);
        $productData['parentProductIds'] = $this->getParentProductIds($product);
    }

    /**
     * Add product media data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addProductMediaData($product, &$productData)
    {
        $mediaConfig = Mage::getModel('catalog/product_media_config');
        $productData['baseImageUrl'] = $mediaConfig->getMediaUrl($product->getImage());
        $productData['smallImageUrl'] = $mediaConfig->getMediaUrl($product->getSmallImage());
        $productData['thumbnailImageUrl'] = $mediaConfig->getMediaUrl($product->getThumbnail());
        $productData['allImages'] = $this->getMediaGallaryImage($product);
    }

    /**
     * Add product relations (related, cross-sell, up-sell)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array &$productData
     * @return void
     */
    protected function addProductRelations($product, &$productData)
    {
        if ($this->showRelatedProducts != 0) {
            $productData['relatedProductIds'] = $product->getRelatedProductIds();
            $productData['crossSellProductIds'] = $product->getCrossSellProductIds();
            $productData['upSellProductIds'] = $product->getUpSellProductIds();
        }
    }

    /**
     * Get price range for a product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getPriceRange($product)
    {
        $priceRange = [];
        if (!is_object($product) || $product->getTypeId() !== 'bundle') {
            return $priceRange;
        }

        $priceModel = $product->getPriceModel();
        if (!is_object($priceModel)) {
            return $priceRange;
        }

        try {
            $pricelist = $priceModel->getTotalPrices($product, null, null, false);
            
            if (is_array($pricelist)) {
                if (isset($pricelist[0])) {
                    $priceRange['lowPrice'] = (float) $pricelist[0];
                }
                if (isset($pricelist[1])) {
                    $priceRange['highPrice'] = (float) $pricelist[1];
                }
            }
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            Mage::logException($e);
            
            // Optionally log a custom message
            $message = sprintf(
                'Error getting price range for product ID %s: %s',
                $product->getId(),
                $e->getMessage()
            );
            Mage::log($message, Zend_Log::ERR, 'convertcart_sync.log', true);
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
        } catch (Exception $e) {
            return null;
        }
    }

    public function getMediaGallaryImage($product)
    {
        $galleryImages = array();
        if (!is_object($product)) {
            return $galleryImages;
        }

        $product->load('media_gallery');
        $mediaGallery = $product->getMediaGalleryImages();

        if (!is_object($mediaGallery)) {
            return $galleryImages;
        }

        foreach ($mediaGallery as $image) {
            $galleryImage = array();
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
        } elseif ($this->queryMethod == 'api') {
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
            $configArray['options'] = array_merge(
                is_array($configArray['options']) ? $configArray['options'] : [],
                is_array($attribute->getPrices()) ? $attribute->getPrices() : []
            );
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
                // Use array_values to get just the values without keys
                $childProductIds[$optionId] = array_values($children);
            }
        }

        return $childProductIds;
    }

    /**
     * Get parent product IDs for a child product
     *
     * @param Mage_Catalog_Model_Product $childProduct
     * @return array
     */
    public function getParentProductIds($childProduct)
    {
        if (!is_object($childProduct)) {
            return [];
        }

        $groupParentIds = Mage::getModel('catalog/product_type_grouped')
            ->getParentIdsByChild($childProduct->getId());
            
        $configParentIds = Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($childProduct->getId());
            
        $bundleParentIds = Mage::getModel('bundle/product_type')
            ->getParentIdsByChild($childProduct->getId());

        // Merge all parent IDs and remove duplicates
        return array_unique(
            array_merge(
                is_array($groupParentIds) ? $groupParentIds : [],
                is_array($configParentIds) ? $configParentIds : [],
                is_array($bundleParentIds) ? $bundleParentIds : []
            )
        );
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
        } elseif ($review->getStatusId() == 2) {
            $reviewDetails['status'] = 'pending';
        } elseif ($review->getStatusId() == 3) {
            $reviewDetails['status'] = 'rejected';
        } else {
            $reviewDetails['status'] = 'other';
        }

        return $reviewDetails;
    }

    public function getAmWishlist($amList)
    {
        $wishlist = array();
        $ccHelper = Mage::getSingleton('convertcart/sync_cc');
        $wishlist['id'] = $ccHelper->getArrValue($amList, 'list_id');
        $wishlist['name'] = $ccHelper->getArrValue($amList, 'title');
        $wishlist['customerId'] = $ccHelper->getArrValue($amList, 'customer_id');
        $wishlist['isDefault'] = $ccHelper->getArrValue($amList, 'is_default');
        $wishlist['createdAt'] = $ccHelper->getArrValue($amList, 'created_at');
        $amItemModel = Mage::getModel('amlist/item');
        if (!is_object($amItemModel)) {
            return $wishlist;
        }
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

    /**
     * Set parameters for the sync operation
     *
     * @param array $params Array of parameters to set
     * @return void
     */
    public function setParams($params)
    {
        if (!is_array($params)) {
            $params = [];
        }

        // Define default values for all parameters
        $defaults = [
            'updatedAt' => '2011-07-29 00:00:00',
            'limit' => 5,
            'offset' => 0,
            'order' => 'asc',
            'storeId' => 1,
            'debug' => 0,
            'productFlatDisabled' => 0,
            'subscriberId' => 0,
            'showRelatedProducts' => 1,
            'queryMethod' => 'custom',
            'customerEmailId' => 0,
            'wishlistId' => 0
        ];

        // Process updatedAt separately as it requires special handling
        $this->updatedAt = isset($params['updatedAt'])
            ? str_ireplace("T", " ", $params['updatedAt'])
            : $defaults['updatedAt'];

        // Set all other parameters using a helper method
        $this->setParameter($params, 'limit', $defaults['limit']);
        $this->setParameter($params, 'offset', $defaults['offset']);
        $this->setParameter($params, 'order', $defaults['order']);
        $this->setParameter($params, 'storeId', $defaults['storeId']);
        $this->setParameter($params, 'debug', $defaults['debug']);
        $this->setParameter($params, 'productFlatDisabled', $defaults['productFlatDisabled']);
        $this->setParameter($params, 'subscriberId', $defaults['subscriberId']);
        $this->setParameter($params, 'showRelatedProducts', $defaults['showRelatedProducts']);
        $this->setParameter($params, 'queryMethod', $defaults['queryMethod']);
        $this->setParameter($params, 'customerEmailId', $defaults['customerEmailId']);
        $this->setParameter($params, 'wishlistId', $defaults['wishlistId']);

        $this->debugMode();
        $this->calculatePage();
    }

    /**
     * Helper method to set a single parameter with a default value
     *
     * @param array $params Source parameters
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return void
     */
    protected function setParameter($params, $key, $default)
    {
        $property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
        if (property_exists($this, $property)) {
            $this->$property = isset($params[$key]) ? $params[$key] : $default;
        }
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
