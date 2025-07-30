<?php
/**
 * Simple test script for ConvertCart event tracking
 * 
 * This script tests the core event tracking functionality
 * without requiring PHPUnit or the full Magento environment
 */

// Simple test class to simulate the ConvertCart Cc model
class Convertcart_Model_Analytics_Cc
{
    protected $_events = [];
    protected $_helper;
    
    public function __construct($helper = null)
    {
        $this->_helper = $helper ?: new Convertcart_Helper_Analytics();
    }
    
    public function storeCcEvents($eventName, $eventData = [], $options = [])
    {
        try {
            if (!$this->_helper->isEnabled()) {
                return false;
            }
            
            if (!is_array($eventData)) {
                $eventData = [];
            }
            
            $options = array_merge([
                'dedupe_key' => null,
                'max_queue_size' => 20,
                'ttl' => 86400,
                'priority' => 'normal'
            ], $options);
            
            // Add event metadata
            $eventData['event_name'] = $eventName;
            $eventData['event_type'] = $this->_helper->getEventType($eventName);
            $eventData = $this->addMetaData($eventData);
            
            // Add event options
            $eventData['_options'] = [
                'stored_at' => time(),
                'priority' => $options['priority'],
                'ttl' => $options['ttl']
            ];
            
            // Check for duplicate events if dedupe_key is provided
            if ($options['dedupe_key']) {
                $dedupeKey = $options['dedupe_key'];
                foreach ($this->_events as $key => $existingEvent) {
                    if (isset($existingEvent['_dedupe_key']) && $existingEvent['_dedupe_key'] === $dedupeKey) {
                        // Update existing event
                        $this->_events[$key] = $eventData;
                        return true;
                    }
                }
                // Add dedupe key to new event
                $eventData['_dedupe_key'] = $dedupeKey;
            }
            
            // Add event to the beginning of the queue (FIFO)
            array_unshift($this->_events, $eventData);
            
            // Remove expired events
            $now = time();
            $this->_events = array_filter($this->_events, function($event) use ($now) {
                return !isset($event['_options']['stored_at']) || 
                       !isset($event['_options']['ttl']) ||
                       ($now - $event['_options']['stored_at']) < $event['_options']['ttl'];
            });
            
            // Limit queue size
            $this->_events = array_slice($this->_events, 0, $options['max_queue_size']);
            
            return true;
            
        } catch (Exception $e) {
            echo "Error storing event: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function fetchCcEvents($options = [])
    {
        try {
            if (!$this->_helper->isEnabled()) {
                return [];
            }
            
            $options = array_merge([
                'limit' => 10,
                'priority' => null,
                'clear' => true
            ], $options);
            
            if (empty($this->_events)) {
                return [];
            }
            
            // Filter out expired events
            $now = time();
            $validEvents = array_filter($this->_events, function($event) use ($now) {
                return !isset($event['_options']['stored_at']) || 
                       !isset($event['_options']['ttl']) ||
                       ($now - $event['_options']['stored_at']) < $event['_options']['ttl'];
            });
            
            // Filter by priority if specified
            if ($options['priority']) {
                $priority = strtolower($options['priority']);
                $validEvents = array_filter($validEvents, function($event) use ($priority) {
                    $eventPriority = isset($event['_options']['priority']) ? 
                        strtolower($event['_options']['priority']) : 'normal';
                    return $eventPriority === $priority;
                });
            }
            
            // Sort events by priority (high to low) and then by timestamp (oldest first)
            usort($validEvents, function($a, $b) {
                $priorityOrder = ['high' => 3, 'normal' => 2, 'low' => 1];
                $aPriority = isset($a['_options']['priority']) ? 
                    strtolower($a['_options']['priority']) : 'normal';
                $bPriority = isset($b['_options']['priority']) ? 
                    strtolower($b['_options']['priority']) : 'normal';
                
                $aScore = $priorityOrder[$aPriority] ?? 2;
                $bScore = $priorityOrder[$bPriority] ?? 2;
                
                if ($aScore === $bScore) {
                    $aTime = $a['_options']['stored_at'] ?? 0;
                    $bTime = $b['_options']['stored_at'] ?? 0;
                    return $aTime - $bTime; // Oldest first
                }
                
                return $bScore - $aScore; // Higher priority first
            });
            
            // Apply limit
            $result = array_slice($validEvents, 0, $options['limit']);
            
            // Remove fetched events if clear is true
            if ($options['clear'] && !empty($result)) {
                $resultIds = array_map(function($event) {
                    return $event['_metadata']['event_id'] ?? null;
                }, $result);
                
                // Keep only events that weren't in the result
                $this->_events = array_filter($this->_events, function($event) use ($resultIds) {
                    $eventId = $event['_metadata']['event_id'] ?? null;
                    return !$eventId || !in_array($eventId, $resultIds);
                });
                
                // Re-index array
                $this->_events = array_values($this->_events);
            }
            
            return $result;
            
        } catch (Exception $e) {
            echo "Error fetching events: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    protected function addMetaData($eventData)
    {
        if (!is_array($eventData)) {
            $eventData = [];
        }
        
        $eventData['_metadata'] = [
            'timestamp' => time(),
            'event_id' => uniqid('test_', true),
            'platform' => 'test',
            'tracking_method' => 'test'
        ];
        
        return $eventData;
    }
    
    public function clearEvents()
    {
        $this->_events = [];
        return true;
    }
}

// Simple test helper class
class Convertcart_Helper_Analytics
{
    public function isEnabled()
    {
        return true;
    }
    
    public function getEventType($event)
    {
        $eventMap = [
            'homepageView' => 'homepageViewed',
            'productView' => 'productViewed',
            'addToCart' => 'productAdded',
            'checkoutView' => 'checkoutViewed'
        ];
        
        return $eventMap[$event] ?? $event . '_mapped';
    }
}

// Test runner
function run_tests()
{
    $test = new Convertcart_Model_Analytics_Cc();
    $passed = 0;
    $failed = 0;
    
    echo "=== Running Event Tracking Tests ===\n\n";
    
    // Test 1: Basic event storage and retrieval
    $test->clearEvents();
    $test->storeCcEvents('testEvent', ['test' => 'data']);
    $events = $test->fetchCcEvents();
    
    if (count($events) === 1 && $events[0]['test'] === 'data') {
        echo "PASS: Basic event storage and retrieval\n";
        $passed++;
    } else {
        echo "FAIL: Basic event storage and retrieval\n";
        $failed++;
    }
    
    // Test 2: Event deduplication
    $test->clearEvents();
    $test->storeCcEvents('testEvent', ['id' => 1, 'value' => 'first'], ['dedupe_key' => 'test1']);
    $test->storeCcEvents('testEvent', ['id' => 1, 'value' => 'updated'], ['dedupe_key' => 'test1']);
    $events = $test->fetchCcEvents();
    
    if (count($events) === 1 && $events[0]['value'] === 'updated') {
        echo "PASS: Event deduplication\n";
        $passed++;
    } else {
        echo "FAIL: Event deduplication\n";
        $failed++;
    }
    
    // Test 3: Event priority
    $test->clearEvents();
    $test->storeCcEvents('testEvent', ['name' => 'low'], ['priority' => 'low']);
    $test->storeCcEvents('testEvent', ['name' => 'normal']);
    $test->storeCcEvents('testEvent', ['name' => 'high'], ['priority' => 'high']);
    $events = $test->fetchCcEvents(['limit' => 3]);
    
    $expectedOrder = ['high', 'normal', 'low'];
    $actualOrder = array_map(function($e) { return $e['name']; }, $events);
    
    if ($actualOrder === $expectedOrder) {
        echo "PASS: Event priority\n";
        $passed++;
    } else {
        echo "FAIL: Event priority (expected: " . implode(', ', $expectedOrder) . 
             ", got: " . implode(', ', $actualOrder) . ")\n";
        $failed++;
    }
    
    // Test 4: Event expiration
    $test->clearEvents();
    $test->storeCcEvents('testEvent', ['test' => 'expire'], ['ttl' => 0]);
    $events = $test->fetchCcEvents();
    
    if (empty($events)) {
        echo "PASS: Event expiration\n";
        $passed++;
    } else {
        echo "FAIL: Event expiration\n";
        $failed++;
    }
    
    // Test 5: Metadata
    $test->clearEvents();
    $test->storeCcEvents('testEvent', ['test' => 'metadata']);
    $events = $test->fetchCcEvents();
    
    if (!empty($events[0]['_metadata']['event_id'])) {
        echo "PASS: Event metadata\n";
        $passed++;
    } else {
        echo "FAIL: Event metadata\n";
        $failed++;
    }
    
    // Summary
    echo "\n=== Test Summary ===\n";
    echo "Passed: $passed\n";
    echo "Failed: $failed\n";
    echo "=================\n";
    
    return $failed === 0;
}

// Run the tests
run_tests();
