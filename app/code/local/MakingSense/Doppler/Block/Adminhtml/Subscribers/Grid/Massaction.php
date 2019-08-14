<?php
/**
 * Mass-action block for subscribers grid
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Subscribers_Grid_Massaction extends Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract
{
    /**
     * Sets Massaction template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('doppler/widget/grid/massaction.phtml');
        $this->setErrorText(Mage::helper('catalog')->jsQuoteEscape(Mage::helper('catalog')->__('Please select subscribers.')));
    }
}
