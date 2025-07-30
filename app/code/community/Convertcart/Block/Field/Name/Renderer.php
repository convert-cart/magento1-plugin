<?php
class Convertcart_Block_Field_Name_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setReadOnly('readonly');
        return parent::_getElementHtml($element);
    }
}
