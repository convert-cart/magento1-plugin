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
                            'wishlistUpdated'   =>  'wishlistUpdated',
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
}