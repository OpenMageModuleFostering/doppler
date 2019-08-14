<?php
/**
 * Subscribers edit form container
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Subscribers_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	/**
	 * Set block group and controller
	 */
	public function __construct()
	{
		$this->_blockGroup = 'makingsense_doppler';
		$this->_controller = 'adminhtml_subscribers';

		parent::__construct();
	}

	/**
	 * Set header text
	 */
	public function getHeaderText()
	{
		return $this->__('Export Customers');
	}

}