# ConvertCart Event Tracking

This document provides an overview of the event tracking system in the ConvertCart Magento 1 extension.

## Table of Contents
- [Event Types](#event-types)
- [Event Storage](#event-storage)
- [Event Retrieval](#event-retrieval)
- [Metadata](#metadata)
- [Best Practices](#best-practices)
- [Testing](#testing)

## Event Types

The extension supports a variety of standard e-commerce events that are automatically tracked. The following event types are available:

### Page View Events
- `homepageView` - When a user views the homepage
- `cmsView` - When a user views a CMS page
- `categoryView` - When a user views a category page
- `productView` - When a user views a product page
- `searchView` - When a user performs a search
- `cartView` - When a user views their cart
- `checkoutView` - When a user views the checkout
- `wishlistView` - When a user views their wishlist
- `compareView` - When a user views the compare products page
- `checkoutSuccess` - When an order is successfully placed

### User Account Events
- `customerRegister` - When a new customer registers
- `customerLogin` - When a customer logs in
- `customerLogout` - When a customer logs out
- `newsletterSubscribe` - When a user subscribes to the newsletter
- `newsletterUnsubscribe` - When a user unsubscribes from the newsletter

### Cart & Checkout Events
- `addToCart` - When a product is added to the cart
- `removeFromCart` - When a product is removed from the cart
- `updateCart` - When the cart is updated
- `couponApplied` - When a coupon is applied
- `couponDenied` - When a coupon is denied
- `couponRemoved` - When a coupon is removed
- `initiateCheckout` - When a user starts the checkout process
- `addShippingInfo` - When shipping information is added
- `addPaymentInfo` - When payment information is added
- `purchase` - When an order is completed
- `orderRefund` - When an order is refunded

### Wishlist & Compare
- `addToWishlist` - When a product is added to the wishlist
- `removeFromWishlist` - When a product is removed from the wishlist
- `wishlistUpdated` - When the wishlist is updated
- `addToCompare` - When a product is added to compare
- `removeFromCompare` - When a product is removed from compare

### Product Interaction
- `productClick` - When a product is clicked
- `productImpression` - When a product is viewed in a list
- `productDetailView` - When a product detail page is viewed
- `addToCartFromList` - When a product is added to cart from a list
- `addToCartFromDetail` - When a product is added to cart from the detail page
- `removeFromCartFromList` - When a product is removed from cart from a list
- `removeFromCartFromDetail` - When a product is removed from cart from the detail page

### Review & Rating
- `reviewSave` - When a product review is saved
- `ratingSave` - When a product rating is saved

## Event Storage

Events are stored in the session and include metadata about the event. The `storeCcEvents` method is used to store events:

```php
/**
 * Store a Convertcart event
 *
 * @param string $eventName The event name (e.g., 'addToCart', 'productView')
 * @param array $eventData The event data to store
 * @param array $options Additional options:
 *   - dedupe_key: Key to use for deduplication
 *   - max_queue_size: Maximum number of events to store (default: 20)
 *   - ttl: Time to live in seconds (default: 86400 - 24 hours)
 *   - priority: Event priority ('low', 'normal', 'high')
 * @return bool True if the event was stored successfully
 */
public function storeCcEvents($eventName, $eventData = array(), $options = array())
```

### Example: Storing an Event

```php
$ccModel = Mage::getSingleton('convertcart_analytics/cc');

// Basic event
$ccModel->storeCcEvents('productView', [
    'product_id' => '123',
    'name' => 'Test Product',
    'price' => 99.99
]);

// Event with options
$ccModel->storeCcEvents('addToCart', [
    'product_id' => '123',
    'quantity' => 2,
    'price' => 99.99
], [
    'dedupe_key' => 'cart_123',
    'priority' => 'high',
    'ttl' => 3600 // 1 hour
]);
```

## Event Retrieval

Events can be retrieved using the `fetchCcEvents` method:

```php
/**
 * Fetch stored Convertcart events
 *
 * @param array $options Additional options:
 *   - limit: Maximum number of events to return (default: 10)
 *   - priority: Filter by priority ('low', 'normal', 'high' or null for all)
 *   - clear: Whether to clear events after fetching (default: true)
 * @return array Array of events, sorted by priority and timestamp
 */
public function fetchCcEvents($options = array())
```

### Example: Retrieving Events

```php
$ccModel = Mage::getSingleton('convertcart_analytics/cc');

// Get all events (up to default limit of 10)
$events = $ccModel->fetchCcEvents();

// Get only high priority events
$highPriorityEvents = $ccModel->fetchCcEvents([
    'priority' => 'high',
    'limit' => 5,
    'clear' => false // Don't remove events after fetching
]);
```

## Metadata

Each event includes metadata that provides additional context about the event:

```php
[
    'event_name' => 'productView',
    'event_type' => 'productViewed',
    'product_id' => '123',
    'name' => 'Test Product',
    'price' => 99.99,
    '_metadata' => [
        'timestamp' => 1627632000,
        'event_id' => 'test_60f7e8a0e1a23',
        'platform' => 'magento1',
        'tracking_method' => 'server_side'
    ],
    '_store' => [
        'id' => '1',
        'code' => 'default',
        'name' => 'Default Store View',
        'website_id' => '1',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'locale' => 'en_US'
    ],
    '_customer' => [
        'id' => '42',
        'email' => 'customer@example.com',
        'group_id' => '1',
        'is_guest' => false,
        'created_at' => '2023-01-01 12:00:00',
        'store_id' => '1'
    ],
    '_device' => [
        'user_agent' => 'Mozilla/5.0...',
        'ip_address' => '192.168.1.1',
        'is_mobile' => false
    ],
    '_page' => [
        'url' => 'https://example.com/product.html',
        'path' => '/product.html',
        'referrer' => 'https://example.com/category.html',
        'search_query' => null
    ]
]
```

## Best Practices

1. **Use Descriptive Event Names**: Choose event names that clearly describe the action being taken.

2. **Include Relevant Data**: Include all relevant data with each event to provide context for analysis.

3. **Use Deduplication**: Use the `dedupe_key` option to prevent duplicate events for the same action.

4. **Set Appropriate Priorities**: Use the `priority` option to indicate the importance of each event.

5. **Set Appropriate TTL**: Use the `ttl` option to control how long events should be stored.

6. **Handle Errors Gracefully**: Always check the return value of `storeCcEvents` and handle errors appropriately.

## Testing

A test script is available at `dev/test_event_tracking.php` that verifies the core functionality of the event tracking system. To run the tests:

```bash
php dev/test_event_tracking.php
```

The test script includes tests for:
- Basic event storage and retrieval
- Event deduplication
- Event priority
- Event expiration
- Event metadata

