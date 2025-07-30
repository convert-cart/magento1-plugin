<?php

/**
 * Customer tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_CustomerTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track customer login
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCustomerLogin($observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            if (!is_object($customer)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('customerLogin');
            $ccData['event_data'] = [
                'customer_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track customer logout
     *
     * @return void
     */
    public function trackCustomerLogout()
    {
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (!is_object($customer) || !$customer->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('customerLogout');
            $ccData['event_data'] = [
                'customer_id' => $customer->getId(),
                'email' => $customer->getEmail()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track customer registration
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackCustomerRegister($observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            if (!is_object($customer)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('customerRegister');
            $ccData['event_data'] = [
                'customer_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'created_at' => $customer->getCreatedAt()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
