<?php
class Convertcart_Analytics_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getEventType($event){
        $event_map = array( 'homepageView'		=>	'viewed_homepage',
							'cmsView'			=>	'viewed_cmspage',
							'categoryView'		=>	'viewed_category',
							'productView'		=>	'viewed_product',
							'searchView'		=>	'viewed_search',
							'customerRegister'	=>	'customer_register',
							'loggedIn'			=>	'logged_in',
							'loggedOut'			=>	'logged_out',
							'cartView'			=>	'viewed_cart',
							'checkoutView'		=>	'viewed_checkout',
							'saveBillingInfo'	=>	'save_billing_info',
							'saveShippingInfo'	=>	'save_shipping_info',
							'updateCart'		=>	'update_cart',
							'addtocart'			=>	'add_to_cart',
							'removeFromCart'	=>	'remove_from_cart',
							'ordered'			=>	'order',
							'addToWishlist'		=>	'add_to_wishlist',
							'removeFromWishlist'=>	'remove_from_wishlist',
							'wishlistView'		=>	'wishlist_view',
							'update_wishlist'	=>	'update_wishlist',
							'addToCompare'		=>	'add_to_compare',
							'removeFromCompare'	=>	'remove_from_compare',
							'compareView'		=>	'compare_view'
							);
		if(isset($event_map[$event]))
			return $event_map[$event];
		else
			return 'default';
	}	

	public function isEnabled()
	{
        $client_id = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_configkeys');

        //determine via config if module active & if init to be sent ..
        //testing mode, comment this line later ..

//        $client_id = '05233918'; //fetch dynamically from config later...
        if(!isset($client_id))
            return ;
        else
        	return 1;
	}

	public function getKey()
	{
        $client_id = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_configkeys');
        if(!isset($client_id))
            return ;
        else
        	return $client_id;
	}
}