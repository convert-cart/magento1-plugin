<?php
class Convertcart_Helper_Analytics extends Mage_Core_Helper_Abstract
{
    /**
     * Get event type mapping between Magento 1 and ConvertCart
     *
     * @param string $event
     * @return string
     */
    public function getEventType($event)
    {
        $eventMap = array(
            // Page View Events
            'homepageView'      => 'homepageViewed',
            'cmsView'           => 'contentPageViewed',
            'categoryView'      => 'categoryViewed',
            'productView'       => 'productViewed',
            'searchView'        => 'productsSearched',
            'cartView'          => 'cartViewed',
            'checkoutView'      => 'checkoutViewed',
            'wishlistView'      => 'wishlistViewed',
            'compareView'       => 'compareViewed',
            'checkoutSuccess'   => 'orderCompleted',
            
            // User Account Events
            'customerRegister'  => 'signedUp',
            'customerLogin'     => 'signedIn',
            'customerLogout'    => 'signedOut',
            'newsletterSubscribe' => 'subscribedToNewsletter',
            'newsletterUnsubscribe' => 'unsubscribedFromNewsletter',
            
            // Cart & Checkout Events
            'addToCart'         => 'productAdded',
            'removeFromCart'    => 'productRemoved',
            'updateCart'        => 'cartUpdated',
            'couponApplied'     => 'couponApplied',
            'couponDenied'      => 'couponDenied',
            'couponRemoved'     => 'couponRemoved',
            'initiateCheckout'  => 'checkoutStarted',
            'addShippingInfo'   => 'shippingInfoAdded',
            'addPaymentInfo'    => 'paymentInfoAdded',
            'purchase'          => 'orderCompleted',
            'orderRefund'       => 'orderRefunded',
            
            // Wishlist & Compare
            'addToWishlist'     => 'productAddedToWishlist',
            'removeFromWishlist'=> 'productRemovedFromWishlist',
            'wishlistUpdated'   => 'wishlistUpdated',
            'addToCompare'      => 'productAddedToCompare',
            'removeFromCompare' => 'productRemovedFromCompare',
            
            // Product Interaction
            'productClick'      => 'productClicked',
            'productImpression' => 'productImpression',
            'productDetailView' => 'productViewed',
            'addToCartFromList' => 'productAddedFromList',
            'addToCartFromDetail' => 'productAddedFromDetail',
            'removeFromCartFromList' => 'productRemovedFromList',
            'removeFromCartFromDetail' => 'productRemovedFromDetail',
            
            // Review & Rating
            'reviewSave'        => 'productReviewed',
            'ratingSave'        => 'productRated',
            
            // Custom Events
            'amastyFavoritesViewed' => 'amastyFavoritesViewed',
            'customEvent'       => 'customEvent'
        );
        if (isset($eventMap[$event])) {
            return $eventMap[$event];
        } else {
            return 'default';
        }
    }

    /**
     * Check if tracking is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getClientKey();
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

        $module = $node->modules;
        if (!is_object($module)) {
            return null;
        }

        return (string)$module->{'Convertcart'}->version;
    }

    public function sanitizeParam($param)
    {
        return strip_tags($param);
    }

    public function getArrValue($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }
}
