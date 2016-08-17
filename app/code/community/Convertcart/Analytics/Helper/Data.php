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

	public function isEnabled(){
		$this->generateKey();
        if( $this->getKey() ) {
        	return 1;
        }
        else
        	return;
	}

	public function getKey()
	{
        $client_id = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_configkeys');
        if(!isset($client_id) or $client_id == '')
            return ;
        else
        	return $client_id;
	}

	public function canSyncCatalog(){
        if( Mage::getStoreConfig('convertcart_options/convercart_config/convercart_catalog') )
        	return 1;
        else{
        	$this->accessDenied();
        }
	}

	public function canSyncCustomer(){
        if( Mage::getStoreConfig('convertcart_options/convercart_config/convercart_customer') )
        	return 1;
        else{
        	$this->accessDenied();
        }
	}

	public function canSyncOrder(){

        if ( Mage::getStoreConfig('convertcart_options/convercart_config/convercart_order') )
        	return 1;
        else
        	$this->accessDenied();
	}

	public function generateKey(){
		$api_key = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_api');
		if(!isset($api_key) or $api_key == ''){
			$api_key = md5(uniqid(rand(), true));
			Mage::getConfig()->saveConfig('convertcart_options/convercart_config/convercart_api', $api_key, 'default', 0);
		}
		return;
	}

	public function authorize(){
	    $request = new Zend_Controller_Request_Http();
		$request_key = $request->getHeader("X-API-Key");
		//testing , uncomment this later
		$request_key = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_api');
		$api_key = Mage::getStoreConfig('convertcart_options/convercart_config/convercart_api');

		//incase api key not yet generated
		if(!isset($api_key) or $api_key == ''){
			$this->generateKey();
        	$this->accessDenied();
		}

		if($api_key != $request_key){
        	$this->accessDenied();
	    }
	}

	public function accessDenied(){
	    Mage::app()->getResponse()
	        ->setHeader('HTTP/1.1','401 Unauthorized')
	        ->setBody('<h1>401 Unauthorized</h1>')
	        ->sendResponse();
	    exit;

	}
}