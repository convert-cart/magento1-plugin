<?php
class Convertcart_Model_Analytics_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        if (!session_id()) {
            Mage::throwException("No session id. Blocking rather than instantiate object early.");
        }
        $this->init('convertcart/analytics_cc_session');
    }
}
