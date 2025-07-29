<?php
/**
 * Test script for ConvertCart event tracking
 * 
 * This script tests the enhanced event tracking functionality to ensure
 * it works correctly with the Magento 1 codebase.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize Magento
$magentoRoot = dirname(dirname(__FILE__));
require_once $magentoRoot . '/app/Mage.php';
umask(0);
Mage::app('default');
Mage::setIsDeveloperMode(true);

// Enable logging
Mage::log('Starting event tracking test', null, 'convertcart_test.log');

// Get the event tracking model
$ccModel = Mage::getSingleton('convertcart_analytics/cc');

// Test 1: Basic event storage and retrieval
$testEvent = array(
    'product_id' => 'test-123',
    'product_name' => 'Test Product',
    'price' => 99.99
);

// Store a test event
$result = $ccModel->storeCcEvents('testEvent', $testEvent, array(
    'dedupe_key' => 'test-123',
    'priority' => 'high',
    'ttl' => 3600
));

// Fetch the event
$events = $ccModel->fetchCcEvents(array('limit' => 1));

// Log results
Mage::log('Test 1 - Basic event storage and retrieval:', null, 'convertcart_test.log');
Mage::log(print_r($events, true), null, 'convertcart_test.log');

// Test 2: Event deduplication
$testEvent2 = array(
    'product_id' => 'test-123',
    'product_name' => 'Updated Test Product',
    'price' => 89.99
);

// Store with same dedupe key - should update existing event
$ccModel->storeCcEvents('testEvent', $testEvent2, array(
    'dedupe_key' => 'test-123',
    'priority' => 'high',
    'ttl' => 3600
));

// Fetch events again
$events = $ccModel->fetchCcEvents(array('limit' => 10));

// Log results
Mage::log('Test 2 - Event deduplication:', null, 'convertcart_test.log');
Mage::log(print_r($events, true), null, 'convertcart_test.log');

// Test 3: Priority-based event retrieval
// Add multiple events with different priorities
$ccModel->storeCcEvents('lowPriorityEvent', array('test' => 'low'), array('priority' => 'low'));
$ccModel->storeCcEvents('normalPriorityEvent', array('test' => 'normal'), array('priority' => 'normal'));
$ccModel->storeCcEvents('highPriorityEvent', array('test' => 'high'), array('priority' => 'high'));

// Fetch only high priority events
$highPriorityEvents = $ccModel->fetchCcEvents(array('priority' => 'high'));

// Log results
Mage::log('Test 3 - High priority events:', null, 'convertcart_test.log');
Mage::log(print_r($highPriorityEvents, true), null, 'convertcart_test.log');

// Test 4: Event expiration
$expiringEvent = array('test' => 'expiring');
$ccModel->storeCcEvents('expiringEvent', $expiringEvent, array('ttl' => 1)); // 1 second TTL

// Wait for event to expire
sleep(2);

// Fetch events - expired event should not be returned
$eventsAfterExpiry = $ccModel->fetchCcEvents();

// Log results
Mage::log('Test 4 - Event expiration:', null, 'convertcart_test.log');
Mage::log(print_r($eventsAfterExpiry, true), null, 'convertcart_test.log');

// Test 5: Event metadata
$metadataTestEvent = array('test' => 'metadata');
$ccModel->storeCcEvents('metadataTest', $metadataTestEvent);
$events = $ccModel->fetchCcEvents(array('limit' => 1));

// Check metadata
$hasMetadata = !empty($events[0]['_metadata']) && 
               !empty($events[0]['_metadata']['event_id']) &&
               !empty($events[0]['_metadata']['timestamp']);

// Log results
Mage::log('Test 5 - Event metadata:', null, 'convertcart_test.log');
Mage::log('Has required metadata: ' . ($hasMetadata ? 'YES' : 'NO'), null, 'convertcart_test.log');
Mage::log(print_r($events[0]['_metadata'] ?? array(), true), null, 'convertcart_test.log');

// Test 6: Batch retrieval
// Clear all events
$ccModel->fetchCcEvents(array('clear' => true));

// Add multiple events
for ($i = 1; $i <= 15; $i++) {
    $ccModel->storeCcEvents("batchEvent$i", array('index' => $i));
}

// Fetch in batches of 5
$batch1 = $ccModel->fetchCcEvents(array('limit' => 5, 'clear' => false));
$batch2 = $ccModel->fetchCcEvents(array('limit' => 5, 'clear' => false));
$remaining = $ccModel->fetchCcEvents(array('limit' => 10, 'clear' => true));

// Log results
Mage::log('Test 6 - Batch retrieval:', null, 'convertcart_test.log');
Mage::log('Batch 1 (5 events): ' . count($batch1), null, 'convertcart_test.log');
Mage::log('Batch 2 (5 events): ' . count($batch2), null, 'convertcart_test.log');
Mage::log('Remaining events: ' . count($remaining), null, 'convertcart_test.log');

// Test 7: Error handling
// Test with invalid input
$invalidResult = $ccModel->storeCcEvents('', 'not_an_array');
$invalidFetch = $ccModel->fetchCcEvents('invalid_options');

// Log results
Mage::log('Test 7 - Error handling:', null, 'convertcart_test.log');
Mage::log('Invalid store result (should be false): ' . ($invalidResult ? 'true' : 'false'), null, 'convertcart_test.log');
Mage::log('Invalid fetch result (should be array): ' . gettype($invalidFetch), null, 'convertcart_test.log');

// Test 8: Real-world event simulation
// Simulate a product view event
$productData = array(
    'id' => '12345',
    'name' => 'Test Product',
    'price' => 199.99,
    'category' => 'Test Category',
    'brand' => 'Test Brand',
    'variant' => 'Blue',
    'position' => 1,
    'list' => 'Search Results'
);

// Store the event with product view data
$ccModel->storeCcEvents('productView', array(
    'products' => array($productData),
    'currency' => 'USD',
    'value' => 199.99
), array(
    'dedupe_key' => 'product_view_12345',
    'priority' => 'high'
));

// Fetch and log the simulated event
$simulatedEvents = $ccModel->fetchCcEvents(array('limit' => 1));
Mage::log('Test 8 - Real-world event simulation:', null, 'convertcart_test.log');
Mage::log(print_r($simulatedEvents, true), null, 'convertcart_test.log');

// Final cleanup
$ccModel->fetchCcEvents(array('clear' => true));

Mage::log('Event tracking tests completed', null, 'convertcart_test.log');
echo "Event tracking tests completed. Check var/log/convertcart_test.log for details.\n";
