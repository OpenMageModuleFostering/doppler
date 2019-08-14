<?php
/**
 * Leadmap edit grid
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Leadmap_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	/**
	 * Set grid ID, default sort by and direction
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->setId('makingsense_doppler_leadmap_grid');
		$this->setDefaultSort('id');
		$this->setDefaultDir('asc');
		$this->setSaveParametersInSession(true);
	}

	/**
	 * Set collection for grid
	 */
    protected function _prepareCollection()
	{
		$collection = Mage::getModel('makingsense_doppler/leadmap')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}

	/**
	 * Set columns for grid
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
			'header' => Mage::helper('makingsense_doppler')->__('Mapping ID'),
			'index'  => 'id',
			'align'  => 'center',
			'width'  => '60px',
			'filter' => false
		));
		$this->addColumn('doppler_field_name', array(
			'header' => Mage::helper('makingsense_doppler')->__('Doppler Field Name'),
			'index'  => 'doppler_field_name'
		));
		$this->addColumn('magento_field_name', array(
			'header' => Mage::helper('makingsense_doppler')->__('Magento Field Name'),
			'index'  => 'magento_field_name'
		));
	}

	/**
	 * Set mass actions for grid items
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('leadmap');

		$this->getMassactionBlock()->addItem('delete', array(
			'label'    => Mage::helper('makingsense_doppler')->__('Delete'),
			'url'      => $this->getUrl('*/*/massDelete'),
			'confirm'  => Mage::helper('makingsense_doppler')->__('Are you sure?')
		));
		
		return parent::_prepareMassaction();
	}

	/**
	 * Set URL for row click
	 */
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
}