<?php
class Convertcart_Analytics_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getEventType($event)
    {
        $eventMap = array(  'homepageView'      =>  'homepageViewed',
                            'cmsView'           =>  'contentPageViewed',
                            'categoryView'      =>  'categoryViewed',
                            'productView'       =>  'productViewed',
                            'searchView'        =>  'productsSearched',
                            'customerRegister'  =>  'registered',
                            'loggedIn'          =>  'loggedIn',
                            'loggedOut'         =>  'loggedOut',
                            'cartView'          =>  'cartViewed',
                            'checkoutView'      =>  'checkoutViewed',
                            'updateCart'        =>  'cartUpdated',
                            'addToCart'         =>  'productAdded',
                            'removeFromCart'    =>  'productRemoved',
                            'ordered'           =>  'orderCompleted',
                            'addToWishlist'     =>  'productAddedToWishlist',
                            'removeFromWishlist'=>  'productRemovedFromWishlist',
                            'wishlistView'      =>  'wishlistViewed',
                            'addToCompare'      =>  'productAddedToCompare',
                            'removeFromCompare' =>  'productRemovedFromCompare',
                            'compareView'       =>  'compareViewed',
                            'couponApplied'     =>  'couponApplied',
                            'couponDenied'      =>  'couponDenied',
                            'couponRemoved'     =>  'couponRemoved',
                            'reviewSave'        =>  'productReviewed'
                        );
        if(isset($eventMap[$event]))
            return $eventMap[$event];
     else
            return 'default';
    }

    public function isEnabled()
    {
        if ($this->getClientKey()) {
            return 1;
        }
        else
            return false;
    }

    public function getClientKey()
    {
        $clientKey = Mage::getStoreConfig('convertcart/config/client_key');
        if(!isset($clientKey) or $clientKey == '')
            return false;
        else
            return $clientKey;
    }

    public function getApiKey()
    {
        $apiKey = Mage::getStoreConfig('convertcart/config/api_key');
        if(!isset($apiKey) or $apiKey == '')
            return false;
        else
            return $apiKey;
    }

    public function getResetApiKey()
    {
        $resetApiKey = Mage::getStoreConfig('convertcart/config/reset_api_key');
        if(!isset($resetApiKey) or $resetApiKey == '')
            return false;
        else
            return $resetApiKey;
    }    

    public function canSyncCatalog()
    {
        if(Mage::getStoreConfig('convertcart/config/catalog'))
            return 1;
        else{
            $this->accessDenied();
        }
    }

    public function canSyncCustomer()
    {
        if(Mage::getStoreConfig('convertcart/config/customer'))
            return 1;
        else{
            $this->accessDenied();
        }
    }

    public function canSyncOrder()
    {
        if (Mage::getStoreConfig('convertcart/config/order'))
            return 1;
        else
            $this->accessDenied();
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

        //testing , uncomment this later
        $requestKey = Mage::getStoreConfig('convertcart/config/api_key');
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
            ->setBody('<h1>401 Unauthorized</h1>')
            ->sendResponse();
        exit;
    }
}