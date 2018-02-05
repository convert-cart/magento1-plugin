<?php
class Convertcart_Sync_Model_Observer
{
    public $logFile = 'cc_sync.log';

    public function generateKey()
    {
        Mage::Helper('convertcart_sync')->generateKey();
    }

    public function productDeleted(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getDataObject();
        if (!$product instanceof Mage_Catalog_Model_Product) {
            return $this;
        }
        try {
            $groupParentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
            $configParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            $bundleParentIds = Mage::getModel('bundle/product_type')->getParentIdsByChild($product->getId());
            $parentIds = array_merge($groupParentIds, $configParentIds);
            $parentIds = array_merge($parentIds, $bundleParentIds);

            if (isset($parentIds) and is_array($parentIds)) {
                $parentIds = implode(',', $parentIds);
            } else {
                $parentIds = null;
            }

            $model = Mage::getModel('convertcart_sync/activity')
                    ->setItemId($product->getId())
                    ->setParentIds($parentIds)
                    ->setAction('product')
                    ->setType('delete')
                    ->save();
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
        }
    }

    public function categoryDeleted(Varien_Event_Observer $observer)
    {
        $category = $observer->getEvent()->getDataObject();
        if (!$category instanceof Mage_Catalog_Model_Category) {
            return $this;
        }

        try {
            $childrenCatIds = $category->getResource()->getChildren($category, true);
            if (is_array($childrenCatIds)) {
                $childrenCatIds = implode(',', $childrenCatIds); 
            } else {
                $childrenCatIds = null;
            }
            $model = Mage::getModel('convertcart_sync/activity')
                    ->setItemId($category->getId())
                    ->setChildrenIds($childrenCatIds)
                    ->setAction('category')
                    ->setType('delete');
            $model->save();
        } catch (Exception $e) {
            Mage::log($e, null, $this->logFile);
        }
    }
}