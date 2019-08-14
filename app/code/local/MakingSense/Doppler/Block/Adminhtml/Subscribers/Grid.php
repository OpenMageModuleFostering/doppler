<?php
/**
 * Subscribers edit grid
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Subscribers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

	/**
	 * Massaction block name
	 *
	 * @var string
	 */
	protected $_massactionBlockName = 'makingsense_doppler/adminhtml_subscribers_grid_massaction';

	/**
	 * Set grid ID, default sort by and direction
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setId('customerGrid');
		$this->setUseAjax(true);
		$this->setDefaultSort('entity_id');
		$this->setSaveParametersInSession(true);
	}

	/**
	 * override the _prepareCollection to add an other attribute to the grid
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = Mage::getResourceModel('customer/customer_collection')
			->addNameToSelect()
			->addAttributeToSelect('email')
			->addAttributeToSelect('created_at')
			->addAttributeToSelect('group_id')
			// Add doppler_synced and status_doppler_sync attribute to grid
			->addAttributeToSelect('doppler_synced')
			->addAttributeToSelect('status_doppler_sync')
			->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
			->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
			->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
			->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
			->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');

		$this->setCollection($collection);

		if ($this->getCollection()) {

			$this->_preparePage();

			$columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
			$dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
			$filter   = $this->getParam($this->getVarNameFilter(), null);

			if (is_null($filter)) {
				$filter = $this->_defaultFilter;
			}

			if (is_string($filter)) {
				$data = $this->helper('adminhtml')->prepareFilterString($filter);
				$this->_setFilterValues($data);
			}
			else if ($filter && is_array($filter)) {
				$this->_setFilterValues($filter);
			}
			else if(0 !== sizeof($this->_defaultFilter)) {
				$this->_setFilterValues($this->_defaultFilter);
			}

			if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
				$dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
				$this->_columns[$columnId]->setDir($dir);
				$this->_setCollectionOrder($this->_columns[$columnId]);
			}

			if (!$this->_isExport) {
				$this->getCollection()->load();
				$this->_afterLoadCollection();
			}
		}

		return $this;
	}

	/**
	 * Set mass actions for grid items
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('customer');

		$dopplerLists = Mage::helper('makingsense_doppler')->getDopplerLists();

		foreach ($dopplerLists as $dopplerList) {
			$listIdentifier = array_search($dopplerList, $dopplerLists);

			$this->getMassactionBlock()->addItem($listIdentifier, array(
				'label'    => $dopplerList,
				'url'      => $this->getUrl('*/*/massExport', array('list' => $listIdentifier))
			));
		}

		return $this;
	}

	/**
	 * Override the _prepareColumns method to add a new column after the 'email' column
	 * if you want the new column on a different position just change the 3rd parameter
	 * of the addColumnAfter method to the id of your desired column
	 */
	protected function _prepareColumns(){
		$this->addColumn('entity_id', array(
			'header'    => Mage::helper('customer')->__('ID'),
			'width'     => '50px',
			'index'     => 'entity_id',
			'type'  	=> 'number'
		));

		$this->addColumn('name', array(
			'header'    => Mage::helper('customer')->__('Name'),
			'index'     => 'name',
			'width'     => '150px'
		));

		$this->addColumn('email', array(
			'header'    => Mage::helper('customer')->__('Email'),
			'width'     => '150',
			'index'     => 'email'
		));

		$groups = Mage::getResourceModel('customer/group_collection')
			->addFieldToFilter('customer_group_id', array('gt'=> 0))
			->load()
			->toOptionHash();

		$this->addColumn('group', array(
			'header'    =>  Mage::helper('customer')->__('Group'),
			'width'     =>  '100',
			'index'     =>  'group_id',
			'type'      =>  'options',
			'options'   =>  $groups,
		));

		$this->addColumn('billing_country_id', array(
			'header'    => Mage::helper('customer')->__('Country'),
			'width'     => '100',
			'type'      => 'country',
			'index'     => 'billing_country_id',
		));

		$this->addColumn('billing_region', array(
			'header'    => Mage::helper('customer')->__('State/Province'),
			'width'     => '100',
			'index'     => 'billing_region',
		));

		if (!Mage::app()->isSingleStoreMode()) {
			$this->addColumn('website_id', array(
				'header'    => Mage::helper('customer')->__('Website'),
				'align'     => 'center',
				'width'     => '80px',
				'type'      => 'options',
				'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
				'index'     => 'website_id',
			));
		}

		$this->addColumnAfter('status_doppler_sync', array(
			'header'    => Mage::helper('customer')->__('Doppler Export Status'),
			'index'     => 'status_doppler_sync',
			'align'		=> 'center',
			'renderer'  => 'makingsense_doppler/adminhtml_subscribers_grid_renderer_status',
			'column_css_class' => 'doppler-highlighted'
		),'website');

		$this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));

	}

	public function getGridUrl()
	{
		return $this->getUrl('*/*/grid', array('_current'=> true));
	}

	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id'=>$row->getId()));
	}
	
}