<?php
/**
 * Defaultlist edit grid
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Defaultlist_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	/**
	 * Set grid ID, default sort by and direction
	 */
	public function __construct (){
		parent::__construct();
		
		$this->setId('makingsense_doppler_defaultlist_grid');
		$this->setFilterVisibility(false);
		$this->setHeadersVisibility(false);
		$this->setPagerVisibility(false);
		$this->setEmptyText($this->__('There is no default Doppler list.'));
		$this->setSaveParametersInSession(true);
	}

	/**
	 * Set collection for grid
	 */
    protected function _prepareCollection (){

		$collection = Mage::getModel('makingsense_doppler/defaultlist')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}

	/**
	 * Set columns for grid
	 */
	protected function _prepareColumns (){
		$this->addColumn('listId', array(
			'header' => Mage::helper('makingsense_doppler')->__('List ID'),
			'index'  => 'listId',
			'filter' => false,
			'width'  => '90px'
		));
		$this->addColumn('name', array(
			'header' => Mage::helper('makingsense_doppler')->__('List Name'),
			'filter' => false,
			'index'  => 'name'
		));
		$this->addColumn('list_status', array(
			'header' => Mage::helper('makingsense_doppler')->__('Status'),
			'index'  => 'list_status',
			'filter' => false,
			'width'  => '600px',
			'align'  => 'center',
			'type'	 => 'options',
			'options' 	=>  array(
								MakingSense_Doppler_Helper_Data::DOPPLER_LIST_STATUS_DELETED => $this->__('The list has been deleted and the new customers auto-import process has been disabled. Please choose a new list.'),
                                MakingSense_Doppler_Helper_Data::DOPPLER_LIST_STATUS_ENABLED => $this->__('New customers auto-import enabled')
							)
		));
	}

	/**
	 * Set URL for table row
	 */
	public function getRowUrl ($row){
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
	
}