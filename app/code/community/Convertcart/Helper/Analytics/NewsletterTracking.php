<?php

/**
 * Newsletter tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_NewsletterTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Track newsletter subscription
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackNewsletterSubscribe($observer)
    {
        try {
            $subscriber = $observer->getEvent()->getSubscriber();
            if (!is_object($subscriber) || !$subscriber->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('newsletterSubscribe');
            $ccData['event_data'] = [
                'email' => $subscriber->getEmail(),
                'subscriber_id' => $subscriber->getId(),
                'subscriber_status' => $subscriber->getStatus()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track newsletter unsubscription
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackNewsletterUnsubscribe($observer)
    {
        try {
            $subscriber = $observer->getEvent()->getSubscriber();
            if (!is_object($subscriber) || !$subscriber->getId()) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('newsletterUnsubscribe');
            $ccData['event_data'] = [
                'email' => $subscriber->getEmail(),
                'subscriber_id' => $subscriber->getId(),
                'subscriber_status' => $subscriber->getStatus()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
