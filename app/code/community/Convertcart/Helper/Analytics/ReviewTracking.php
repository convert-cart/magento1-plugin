<?php

/**
 * Review tracking helper
 *
 * @category   Convertcart
 * @package    Convertcart_Analytics
 * @author     Convertcart
 */
class Convertcart_Helper_Analytics_ReviewTracking extends Mage_Core_Helper_Abstract
{
    protected $logFile = 'cc_analytics.log';

    /**
     * Get review data from review object
     *
     * @param mixed $review
     * @return array
     */
    protected function getReviewData($review)
    {
        if (!is_object($review)) {
            return [];
        }

        return [
            'review_id' => $review->getId() ?: 0,
            'review_title' => $review->getTitle() ?: '',
            'review_detail' => $review->getDetail() ?: '',
            'nickname' => $review->getNickname() ?: '',
            'product_id' => $review->getEntityPkValue() ?: 0,
            'rating_value' => $this->getReviewRatingValue($review)
        ];
    }

    /**
     * Get rating value from review
     *
     * @param mixed $review
     * @return float
     */
    protected function getReviewRatingValue($review)
    {
        if (!method_exists($review, 'getRating')) {
            return 0.0;
        }

        $rating = $review->getRating();
        if (!is_object($rating) || !method_exists($rating, 'getValue')) {
            return 0.0;
        }

        return (float)$rating->getValue();
    }

    /**
     * Track review save
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackReviewSave($observer)
    {
        try {
            $review = $observer->getEvent()->getObject();
            $reviewData = $this->getReviewData($review);
            
            if (empty($reviewData)) {
                return;
            }

            $ccData = [
                'event_type' => Mage::helper('convertcart/analytics_cc')->getEventType('reviewSave'),
                'event_data' => $reviewData,
                'meta_data' => Mage::getSingleton('convertcart_analytics/cc')->insertMeta()
            ];
            
            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }

    /**
     * Track review view
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackReviewView($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            if (!is_object($product)) {
                return;
            }

            $ccModel = Mage::getSingleton('convertcart_analytics/cc');
            $ccData = [];
            $ccData['event_type'] = Mage::helper('convertcart/analytics_cc')->getEventType('reviewView');
            $ccData['event_data'] = [
                'product_id' => $product->getId(),
                'product_name' => $product->getName(),
                'product_sku' => $product->getSku()
            ];
            $ccData['meta_data'] = $ccModel->insertMeta();
            $ccModel->storeData($ccData);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
    }
}
