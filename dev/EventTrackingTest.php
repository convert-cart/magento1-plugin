<?php
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConvertCart event tracking
 * 
 * This class tests the enhanced event tracking functionality
 */
class EventTrackingTest extends TestCase
{
    /**
     * @var Convertcart_Model_Analytics_Cc
     */
    protected $_ccModel;
    
    /**
     * @var Convertcart_Helper_Analytics
     */
    protected $_analyticsHelper;
    
    protected function setUp()
    {
        // Mock the session
        $sessionMock = $this->getMockBuilder('Mage_Core_Model_Session_Abstract')
            ->setMethods(array('getCcEvents', 'setCcEvents'))
            ->getMockForAbstractClass();
            
        // Mock the helper
        $this->_analyticsHelper = $this->getMockBuilder('Convertcart_Helper_Analytics')
            ->setMethods(array('isEnabled', 'getEventType'))
            ->getMock();
            
        // Configure the helper mock
        $this->_analyticsHelper->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
            
        $this->_analyticsHelper->expects($this->any())
            ->method('getEventType')
            ->willReturnCallback(function($event) {
                return $event . '_mapped';
            });
            
        // Create the Cc model with the mocked session
        $this->_ccModel = $this->getMockBuilder('Convertcart_Model_Analytics_Cc')
            ->setMethods(array('_getSession', 'addMetaData'))
            ->getMock();
            
        $this->_ccModel->expects($this->any())
            ->method('_getSession')
            ->willReturn($sessionMock);
            
        $this->_ccModel->expects($this->any())
            ->method('addMetaData')
            ->willReturnCallback(function($data) {
                if (!is_array($data)) {
                    $data = array();
                }
                $data['_metadata'] = array(
                    'timestamp' => time(),
                    'event_id' => uniqid('test_', true)
                );
                return $data;
            });
            
        // Set the helper on the model using reflection
        $reflection = new ReflectionClass($this->_ccModel);
        $property = $reflection->getParentClass()->getProperty('_helper');
        $property->setAccessible(true);
        $property->setValue($this->_ccModel, $this->_analyticsHelper);
    }
    
    public function testStoreAndRetrieveEvent()
    {
        // Set up session expectations
        $session = $this->_ccModel->_getSession();
        $session->expects($this->any())
            ->method('getCcEvents')
            ->willReturn(array());
            
        $session->expects($this->once())
            ->method('setCcEvents')
            ->with($this->isType('array'));
        
        // Store an event
        $eventData = array('test' => 'data');
        $result = $this->_ccModel->storeCcEvents('testEvent', $eventData);
        
        // Assert the event was stored successfully
        $this->assertTrue($result);
    }
    
    public function testEventDeduplication()
    {
        $events = array();
        $dedupeKey = 'test_dedupe_key';
        
        // Set up session to track stored events
        $session = $this->_ccModel->_getSession();
        $session->expects($this->any())
            ->method('getCcEvents')
            ->willReturnCallback(function() use (&$events) {
                return $events;
            });
            
        $session->expects($this->exactly(2))
            ->method('setCcEvents')
            ->willReturnCallback(function($newEvents) use (&$events) {
                $events = $newEvents;
            });
        
        // Store first event
        $eventData1 = array('id' => 1, 'value' => 'first');
        $result1 = $this->_ccModel->storeCcEvents('testEvent', $eventData1, array(
            'dedupe_key' => $dedupeKey
        ));
        
        // Store second event with same dedupe key
        $eventData2 = array('id' => 1, 'value' => 'updated');
        $result2 = $this->_ccModel->storeCcEvents('testEvent', $eventData2, array(
            'dedupe_key' => $dedupeKey
        ));
        
        // Fetch events
        $storedEvents = $this->_ccModel->fetchCcEvents(array('limit' => 10));
        
        // Assert only one event exists and it's the updated one
        $this->assertCount(1, $storedEvents);
        $this->assertEquals('updated', $storedEvents[0]['value']);
    }
    
    public function testEventPriority()
    {
        $events = array();
        
        // Set up session to track stored events
        $session = $this->_ccModel->_getSession();
        $session->expects($this->any())
            ->method('getCcEvents')
            ->willReturnCallback(function() use (&$events) {
                return $events;
            });
            
        $session->expects($this->exactly(3))
            ->method('setCcEvents')
            ->willReturnCallback(function($newEvents) use (&$events) {
                $events = $newEvents;
            });
        
        // Store events with different priorities
        $this->_ccModel->storeCcEvents('lowPriority', array('name' => 'low'), array('priority' => 'low'));
        $this->_ccModel->storeCcEvents('normalPriority', array('name' => 'normal')); // Default is normal
        $this->_ccModel->storeCcEvents('highPriority', array('name' => 'high'), array('priority' => 'high'));
        
        // Fetch events - should be ordered by priority (high, normal, low)
        $fetchedEvents = $this->_ccModel->fetchCcEvents(array('limit' => 3));
        
        // Assert events are in correct order
        $this->assertCount(3, $fetchedEvents);
        $this->assertEquals('high', $fetchedEvents[0]['name']);
        $this->assertEquals('normal', $fetchedEvents[1]['name']);
        $this->assertEquals('low', $fetchedEvents[2]['name']);
    }
    
    public function testEventExpiration()
    {
        $events = array();
        
        // Set up session to track stored events
        $session = $this->_ccModel->_getSession();
        $session->expects($this->any())
            ->method('getCcEvents')
            ->willReturnCallback(function() use (&$events) {
                return $events;
            });
            
        $session->expects($this->exactly(2))
            ->method('setCcEvents')
            ->willReturnCallback(function($newEvents) use (&$events) {
                $events = $newEvents;
            });
        
        // Store an event with a very short TTL
        $this->_ccModel->storeCcEvents('expiringEvent', array('test' => 'expire'), array('ttl' => 0));
        
        // Fetch events - should be empty because the event expired
        $fetchedEvents = $this->_ccModel->fetchCcEvents();
        
        // Assert no events were returned
        $this->assertEmpty($fetchedEvents);
    }
    
    public function testEventMetadata()
    {
        $session = $this->_ccModel->_getSession();
        $session->expects($this->any())
            ->method('getCcEvents')
            ->willReturn(array());
            
        $session->expects($this->once())
            ->method('setCcEvents')
            ->with($this->callback(function($events) {
                $this->assertCount(1, $events);
                $event = $events[0];
                $this->assertArrayHasKey('_metadata', $event);
                $this->assertArrayHasKey('timestamp', $event['_metadata']);
                $this->assertArrayHasKey('event_id', $event['_metadata']);
                return true;
            }));
        
        // Store an event
        $this->_ccModel->storeCcEvents('testEvent', array('test' => 'data'));
    }
}

// Run the tests if this file is executed directly
if (PHP_SAPI === 'cli' && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    // Simple test runner
    $test = new EventTrackingTest();
    $test->setName('Event Tracking Tests');
    
    $methods = get_class_methods($test);
    $testMethods = array_filter($methods, function($method) {
        return strpos($method, 'test') === 0;
    });
    
    $results = array(
        'passed' => 0,
        'failed' => 0,
        'errors' => array()
    );
    
    foreach ($testMethods as $method) {
        try {
            $test->setUp();
            $test->$method();
            echo ".";
            $results['passed']++;
        } catch (Exception $e) {
            echo "F";
            $results['failed']++;
            $results['errors'][] = sprintf(
                "Test %s failed: %s\n%s",
                $method,
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }
    
    // Output summary
    echo "\n\n";
    echo sprintf(
        "Tests: %d passed, %d failed\n\n",
        $results['passed'],
        $results['failed']
    );
    
    if (!empty($results['errors'])) {
        echo "FAILURES!\n\n";
        foreach ($results['errors'] as $error) {
            echo $error . "\n\n";
        }
    }
    
    exit($results['failed'] > 0 ? 1 : 0);
}
