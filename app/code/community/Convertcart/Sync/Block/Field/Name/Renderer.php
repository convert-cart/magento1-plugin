<?php
class Convertcart_Sync_Block_Field_Name_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml($element)
    {
        $element->setReadOnly('readonly');
        return parent::_getElementHtml($element);
    }
}