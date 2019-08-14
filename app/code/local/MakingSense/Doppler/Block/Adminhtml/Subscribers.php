<?php
/**
 * Subscribers grid container
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Subscribers extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	/**
	 * Set controller, block group and labels for title and remove top-right button from grid
	 */
	public function __construct ()
	{
		$this->_controller = 'adminhtml_subscribers';
		$this->_blockGroup = 'makingsense_doppler';
		$this->_headerText = Mage::helper('makingsense_doppler')->__('Doppler Subscribers');
		parent::__construct();

		// Remove add button from this grid
		$this->_removeButton('add');
	}

}