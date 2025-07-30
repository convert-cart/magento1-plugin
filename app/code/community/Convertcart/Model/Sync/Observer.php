<?php
/**
 * ConvertCart Sync Observer
 *
 * Handles product and category deletion events for ConvertCart synchronization
 */
class Convertcart_Model_Sync_Observer
{
    /**
     * Log file for sync operations
     *
     * @var string
     */
    public $logFile = 'cc_sync.log';

    /**
     * Generate API key for ConvertCart integration
     *
     * @return void
     */
    public function generateKey()
    {
        Mage::Helper('convertcart/sync_cc')->generateKey();
    }

    /**
     * Handle product deletion events
     *
     * Records product deletion in ConvertCart activity log, including parent product relationships
     *
     * @param Varien_Event_Observer $observer Event observer containing product data
     * @return $this
     */
    public function productDeleted(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getDataObject();
        if (!$product instanceof Mage_Catalog_Model_Product) {
            return $this;
        }

        try {
            // Initialize arrays to prevent undefined variable warnings
            $groupParentIds = Mage::getModel('catalog/product_type_grouped')
                                ->getParentIdsByChild($product->getId());
            $configParentIds = Mage::getModel('catalog/product_type_configurable')
                                ->getParentIdsByChild($product->getId());
            $bundleParentIds = Mage::getModel('bundle/product_type')
                                ->getParentIdsByChild($product->getId());
            
            // Ensure arrays are initialized
            if (!is_array($groupParentIds)) {
                $groupParentIds = array();
            }
            if (!is_array($configParentIds)) {
                $configParentIds = array();
            }
            if (!is_array($bundleParentIds)) {
                $bundleParentIds = array();
            }
            
            $parentIds = array_merge($groupParentIds, $configParentIds);
            $parentIds = array_merge($parentIds, $bundleParentIds);

            if (is_array($parentIds) && !empty($parentIds)) {
                $parentIds = implode(',', $parentIds);
            } else {
                $parentIds = null;
            }

            Mage::getModel('convertcart/sync_cc_activity')
                ->setItemId($product->getId())
                ->setParentIds($parentIds)
                ->setAction('product')
                ->setType('delete')
                ->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
        
        return $this;
    }

    /**
     * Handle category deletion events
     *
     * Records category deletion in ConvertCart activity log, including child category relationships
     *
     * @param Varien_Event_Observer $observer Event observer containing category data
     * @return $this
     */
    public function categoryDeleted(Varien_Event_Observer $observer)
    {
        $category = $observer->getEvent()->getDataObject();
        if (!$category instanceof Mage_Catalog_Model_Category) {
            return $this;
        }

        try {
            // Get child categories if any
            $childrenCatIds = $category->getResource()->getChildren($category, true);
            
            // Format child category IDs for storage
            if (is_array($childrenCatIds) && !empty($childrenCatIds)) {
                $childrenCatIds = implode(',', $childrenCatIds);
            } else {
                $childrenCatIds = null;
            }

            // Record category deletion in activity log
            $model = Mage::getModel('convertcart/sync_cc_activity')
                    ->setItemId($category->getId())
                    ->setChildrenIds($childrenCatIds)
                    ->setAction('category')
                    ->setType('delete');
            $model->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->logFile);
        }
        
        return $this;
    }
}
