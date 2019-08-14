<?php
/**
 * Doppler module system config form field
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Block_System_Config_Form_Fieldset_Connection_Test extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Add jQuery to system configuration section
     */
    public function _prepareLayout()
    {
        $head = $this->getLayout()->getBlock('head');
        $head->addJs('lib/jquery/jquery-1.10.2.js');
        $head->addJs('lib/jquery/noconflict.js');

        return parent::_prepareLayout();
    }

    /**
     * Customize test connection system configuration element
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
        {
            $block = Mage::app()->getLayout()->createBlock('adminhtml/widget_form_renderer_element')
                ->setTemplate('doppler/form/testconnection.phtml');

            $usernameValue = Mage::getStoreConfig('doppler/connection/username');
            $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

            if($usernameValue != '' && $apiKeyValue != '')
            {
                $status = Mage::helper('makingsense_doppler')->testAPIConnection();
                $block->setStatusCode($status);
            }

            return $block->toHtml();
        }


}