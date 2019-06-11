<?php
class Convertcart_Sync_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_USE_PRODUCT_FLAT = 'catalog/frontend/flat_catalog_product';
    const XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY = 'catalog/frontend/flat_catalog_category';

    public function isEnabled()
    {
        if ($this->getClientKey()) {
            return true;
        } else {
            return false;
        }
    }

    public function getClientKey()
    {
        $clientKey = Mage::getStoreConfig('convertcart/config/client_key');
        if (!isset($clientKey) or $clientKey == '') {
            return false;
        } else {
            return $clientKey;
        }
    }

    public function getApiKey()
    {
        $apiKey = Mage::getStoreConfig('convertcart/config/api_key');
        if (!isset($apiKey) or $apiKey == '') {
            return false;
        } else {
            return $apiKey;
        }

    }

    public function getResetApiKey()
    {
        $resetApiKey = Mage::getStoreConfig('convertcart/config/reset_api_key');
        if (!isset($resetApiKey) or $resetApiKey == '') {
            return false;
        } else {
            return $resetApiKey;
        }
    }

    /**
     * Deprecated since 1.1.5
     */
    public function canSyncCatalog()
    {
        if (Mage::getStoreConfig('convertcart/config/catalog')) {
            return true;
        } else {
            $this->accessDenied();
        }
    }

    /**
     * Deprecated since 1.1.5
     */
    public function canSyncCustomer()
    {
        if (Mage::getStoreConfig('convertcart/config/customer')) {
            return true;
        } else {
            $this->accessDenied();
        }
    }

    /**
     * Deprecated since 1.1.5
     */
    public function canSyncOrder()
    {
        if (Mage::getStoreConfig('convertcart/config/order')) {
            return true;
        } else {
            $this->accessDenied();
        }
    }

    public function generateKey()
    {
        $apiKey = $this->getApiKey();
        $resetApiKey = $this->getResetApiKey();
        if ((!isset($apiKey) or $apiKey == '') or $resetApiKey ) {
            try {
                $apiKey = md5(uniqid(rand(), true));
                Mage::getConfig()->saveConfig('convertcart/config/api_key', $apiKey, 'default', 0);
                Mage::getConfig()->saveConfig('convertcart/config/reset_api_key', 0, 'default', 0);
                Mage::app()->getCacheInstance()->cleanType('config');
                if (isset($apiKey) and $apiKey != '') { //dont display first time
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('ConvertCart Api key generated successfully')
                    );
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('adminhtml')->__('Unable to reset api key')
                );
            }
        }

        return false;
    }

    public function authorize()
    {
        $request = new Zend_Controller_Request_Http();
        $requestKey = $request->getHeader("X-API-Key");
        $apiKey = Mage::getStoreConfig('convertcart/config/api_key');

        //incase api key not yet generated
        if (!isset($apiKey) or $apiKey == '') {
            $this->generateKey();
            $this->accessDenied();
        }

        if ($apiKey != $requestKey) {
            $this->accessDenied();
        }
    }

    public function accessDenied()
    {
        Mage::app()->getResponse()
            ->setHeader('HTTP/1.1', '401 Unauthorized')
            ->setBody('<h1>401 Unauthorized - Invalid Credentials</h1>')
            ->sendResponse();
        exit;
    }

    public function sendErrorResponse($errorMessage = 'Some Error Occurred')
    {
        $response = array('error' => $errorMessage);
        Mage::app()->getResponse()
            ->setHeader('HTTP/1.1', '500 Internal Server Error')
            ->setHeader('Content-type', 'application/json')
            ->setBody(json_encode($response));
    }

    public function sendSuccessResponse($data = array())
    {
        Mage::app()->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody(json_encode($data));
    }

    public function getModuleVersion()
    {
        $config = Mage::getConfig();
        if (!is_object($config)) {
            return null;
        }

        $node = $config->getNode();
        if (!is_object($node)) {
            return null;
        }

        $version = (string)$node->modules->Convertcart_Sync->version;
        return $version;
    }

    public function getCartItems($quote)
    {
        $cart = array();
        if (!is_object($quote)) {
            return $cart;
        }

        $currency = null;
        $store = Mage::app()->getStore();
        if (is_object($store)) {
            $currency = $store->getCurrentCurrencyCode();
        }

        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            $cartItem['id'] = $item->getProductId();
            $cartItem['name'] = str_replace("'", "", $item->getName());
            $cartItem['price'] = Mage::helper('core')->currency($item->getPrice(), false, false);
            $cartItem['currency'] = $currency;
            $cartItem['quantity'] = $item->getQty();
            $cartItem['sku'] = $item->getSku();
            $product = $item->getProduct();
            if (is_object($product)) {
                $cartItem['url'] = $product->getProductUrl();
            }

            $resource = Mage::getSingleton('catalog/product')->getResource();
            if (is_object($resource)) {
                $resource = Mage::getSingleton('catalog/product')->getResource();
                if (is_object($store))
                    $imagePath = $resource->getAttributeRawValue($item->getProductId(), "image", $store);
                    $imageUrl = $this->getImageUrl($imagePath);
                    if ($imageUrl != null) {
                        $cartItem['image'] = $imageUrl;
                    }
            }

            $cart[] = $cartItem;
        }

        return $cart;
    }

    public function getImageUrl($imagePath)
    {
        $imageUrl = null;
        if ($imagePath != null and $imagePath != "no_selection") {
            $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $imagePath;
        }

        return $imageUrl;
    }

    public function getPrice($price)
    {
        if (!isset($price))
            return 0;

        return Mage::helper('core')->currency($price, false, false);
    }

    public function isProductFlatEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_USE_PRODUCT_FLAT);
    }

    public function isCategoryFlatEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY);
    }
}