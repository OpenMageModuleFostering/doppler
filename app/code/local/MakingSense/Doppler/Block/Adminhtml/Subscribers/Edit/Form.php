<?php
/**
 * Subscribers edit form
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Block_Adminhtml_Subscribers_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

	/**
	 * Prepare form before rendering HTML
	 *
	 * @return MakingSense_Doppler_Block_Adminhtml_Subscribers_Edit_Form
	 */
	protected function _prepareForm()
	{
		$model = Mage::registry('subscribers_data');

		if (Mage::helper('makingsense_doppler')->testAPIConnection() != '200')
		{
			return parent::_prepareForm();
		}

		$form = new Varien_Data_Form(array(
			'id' => 'edit_form',
			'action' => $this->getUrl("*/*/save", array('id' => $this->getRequest()->getParam('id'))),
			'method' => 'post'
		));

        $fieldset = $form->addFieldset('subscribers_form', array(
			'legend' => Mage::helper('makingsense_doppler')->__('Export customer to list')
		));

		if ($model->getId()){
			$fieldset->addField('firstname', 'text', array(
				'label'     => Mage::helper('makingsense_doppler')->__('First Name'),
				'required'  => false,
				'readonly' => true,
				'name'      => 'firstname',
				'class'		=> 'non-editable'
			));
			$fieldset->addField('lastname', 'text', array(
				'label'     => Mage::helper('makingsense_doppler')->__('Last Name'),
				'required'  => false,
				'readonly' => true,
				'name'      => 'lastname',
				'class'		=> 'non-editable'
			));
			$fieldset->addField('email', 'text', array(
				'label'     => Mage::helper('makingsense_doppler')->__('Email'),
				'required'  => false,
				'readonly' => true,
				'name'      => 'email',
				'class'		=> 'non-editable'
			));
			$fieldset->addField('entity_id', 'hidden', array(
				'name' => 'entity_id',
			));
		}

		$dopplerLists = Mage::helper('makingsense_doppler')->getDopplerLists();

		$fieldset->addField('doppler_list', 'select', array(
			'label'     => Mage::helper('makingsense_doppler')->__('Doppler List'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'doppler_list',
			'values' => $dopplerLists
		));

		$form->setUseContainer(true);
		$form->setValues($model->getData());
		$this->setForm($form);
		
		return parent::_prepareForm();
	}

}