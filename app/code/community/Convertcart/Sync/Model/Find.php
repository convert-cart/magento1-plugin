<?php
class Convertcart_Sync_Model_Find extends Mage_Core_Model_Session_Abstract
{
    public function getCustomer($params)
    {
        if (!isset($params['id']) and !isset($params['email'])) {
            return null;
        }

        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);
        if (isset($params['id'])) {
            $customerData = Mage::getModel('customer/customer')->load($params['id'])
                            ->getData();
        } elseif (isset($params['email'])) {
            $websiteId = Mage::getModel('core/store')->load($ccModel->storeId)->getWebsiteId();
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
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);
        $orderData = Mage::getModel('sales/order_api')->info($params['id']);

        return $orderData;
    }//getOrder function ends

    public function getProduct($params)
    {
        if (!isset($params['id']) and !isset($params['sku'])) {
            return null;
        }

        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        if (isset($params['id'])) {
            $product = Mage::getModel('catalog/product')
                        ->load($params['id']);
        } elseif (isset($params['sku'])) {
            $product = Mage::getModel('catalog/product')
                        ->loadByAttribute('sku', $params['sku']);
        }
        $productData = $ccModel->getProductData($product);

        return $productData;
    }//getProduct function ends

    public function getCategory($params)
    {
        if (!isset($params['id'])) {
            return null;
        }
        $ccModel = Mage::getSingleton('convertcart_sync/cc');
        $ccModel->setParams($params);

        $category = Mage::getModel('convertcart_sync/category')
                    ->load($params['id']);

        $categoryData = Mage::getSingleton('convertcart_sync/cc')->getCategoryData($category);

        return $categoryData;
    }// getCategory function ends
}