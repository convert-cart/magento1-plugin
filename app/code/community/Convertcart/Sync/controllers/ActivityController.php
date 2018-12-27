<?php
class Convertcart_Sync_ActivityController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        Mage::helper('convertcart_sync')->authorize();
        return parent::preDispatch();
    }

    public function getAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            $limit = isset($params['limit']) ? $params['limit'] : 10;
            $page = isset($params['page']) ? $params['page'] : 1;
            $collection = Mage::getModel('convertcart_sync/activity')
                        ->getCollection()
                        ->setPageSize($limit)
                        ->setCurPage($page);
            $data = $collection->getData();
            Mage::helper('convertcart_sync')->sendSuccessResponse($data);
        } catch (Exception $e) {
            Mage::helper('convertcart_sync')->sendErrorResponse($e->getMessage());
        }
    }

    public function deleteAction()
    {
        try {
            $params = Mage::getModel('convertcart_sync/cc')->getParams();
            if (!isset($params['id'])) {
                throw new Exception('No id specified', 400);
            }

            $id = $params['id'];
            $model = Mage::getModel('convertcart_sync/activity');

            $collection = $model->getCollection()
                        ->addFieldToFilter('id', array('lteq' => $id));
            foreach ($collection as $record) {
                $model->setId($record->getId())
                      ->delete();
            }

            Mage::helper('convertcart_sync')->sendSuccessResponse();
        } catch (Exception $e){
            if ($e->getCode() == 400) {
                $response = array('error' => $e->getMessage());
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '400 Bad Request')
                    ->setHeader('Content-type', 'application/json')
                    ->setBody(json_encode($response));
            } else {
                Mage::helper('convertcart_sync')->sendErrorResponse($e->getMessage());
            }
        }
    }
}