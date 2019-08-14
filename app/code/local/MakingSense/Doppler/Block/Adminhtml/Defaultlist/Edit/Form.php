<?php
/**
 * Defaultlist edit form
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Defaultlist_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * Prepare form before rendering HTML
	 *
	 * @return MakingSense_Doppler_Block_Adminhtml_Lists_Edit_Form
	 */
	protected function _prepareForm()
	{

		$model = Mage::registry('defaultlist_data');

		$form = new Varien_Data_Form(array(
			'id' => 'edit_form',
			'action' => $this->getUrl("*/*/save", array('id' => $this->getRequest()->getParam('id'))),
			'method' => 'post'
		));

		$fieldset = $form->addFieldset('defaultlist_form', array(
			'legend' => Mage::helper('makingsense_doppler')->__('Default List')
		));

		$dopplerLists = Mage::helper('makingsense_doppler')->getDopplerLists();

		$fieldset->addField('doppler_list_id', 'select', array(
			'label'     => Mage::helper('makingsense_doppler')->__('Doppler List Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'doppler_list_id',
			'values' => $dopplerLists
		));

		if ($model->getId())
		{
			$fieldset->addField('id', 'hidden', array(
				'name' => 'id',
            ));
		}

		$form->setUseContainer(true);
		$form->setValues($model->getData());
		$this->setForm($form);

		return parent::_prepareForm();

	}


}