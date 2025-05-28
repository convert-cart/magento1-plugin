<?php
class Convertcart_Model_Sync_ActivityController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart/sync_cc')->authorize();
        return parent::preDispatch();
    }

    public function getAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            $limit = isset($params['limit']) ? $params['limit'] : 10;
            $page = isset($params['page']) ? $params['page'] : 1;
            $collection = Mage::getModel('convertcart/sync_cc_activity')
                        ->getCollection()
                        ->setPageSize($limit)
                        ->setCurPage($page);
            $data = $collection->getData();
            Mage::helper('convertcart/sync_cc')->sendSuccessResponse($data);
        } catch (Exception $e) {
            Mage::helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
        }
    }

    public function deleteAction()
    {
        try {
            $params = Mage::getModel('convertcart/sync_cc')->getParams();
            if (!isset($params['id'])) {
                throw new Exception('No id specified', 400);
            }

            $id = $params['id'];
            $model = Mage::getModel('convertcart/sync_cc_activity');

            $collection = $model->getCollection()
                        ->addFieldToFilter('id', array('lteq' => $id));
            foreach ($collection as $record) {
                $model->setId($record->getId())
                      ->delete();
            }

            Mage::helper('convertcart/sync_cc')->sendSuccessResponse();
        } catch (Exception $e){
            if ($e->getCode() == 400) {
                $response = array('error' => $e->getMessage());
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '400 Bad Request')
                    ->setHeader('Content-type', 'application/json')
                    ->setBody(json_encode($response));
            } else {
                Mage::helper('convertcart/sync_cc')->sendErrorResponse($e->getMessage());
            }
        }
    }
}