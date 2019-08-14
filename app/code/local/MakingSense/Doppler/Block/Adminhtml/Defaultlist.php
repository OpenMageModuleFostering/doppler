<?php
/**
 * Defaultlist grid container
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Defaultlist extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	/**
	 * Set controller, block group and labels for title and top-right button
	 */
	public function __construct ()
	{
		$this->_controller = 'adminhtml_defaultlist';
		$this->_blockGroup = 'makingsense_doppler';
		$this->_headerText = Mage::helper('makingsense_doppler')->__('Default Doppler List');
		$this->_addButtonLabel = Mage::helper('makingsense_doppler')->__('Set Default List');
	
		parent::__construct();
	}

}