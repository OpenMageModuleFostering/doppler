<?php
/**
 * Leadmap admin page controller
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Adminhtml_LeadmapController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Set active menu
     */
    protected function initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('makingsense_doppler/leadmap');

        return $this;
    }

    /**
     * Create block for index action
     */
    public function indexAction()
    {
        $statusCode = Mage::helper('makingsense_doppler')->testAPIConnection();
        $errorStatusCode = false;

        if (!$statusCode)
        {
            $errorStatusCode = true;
            Mage::getSingleton('core/session')->addError($this->__('Please, add your credentials for the Doppler API in System -> Configuration -> Doppler'));
        } else {
            if ($statusCode != '200')
            {
                if ($statusCode == '404' || $statusCode == '4040')
                {
                    Mage::getSingleton('core/session')->addError($this->__('The Doppler API is not currently available, please try later'));
                } else {
                    Mage::getSingleton('core/session')->addError($this->__('Your credentials are not valid, please check your username and API key and try again'));
                    $errorStatusCode = true;
                }
            }
        }

        if($errorStatusCode)
        {
            $this->initAction()
                ->renderLayout();
        } else {
            $this->initAction()
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_leadmap'))
                ->renderLayout();
        }
    }

    /**
     * Forward new to edit action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit action logic
     */
    public function editAction()
    {
        $statusCode = Mage::helper('makingsense_doppler')->testAPIConnection();
        $errorStatusCode = false;

        if (!$statusCode)
        {
            $errorStatusCode = true;
            Mage::getSingleton('core/session')->addError($this->__('Please, add your credentials for the Doppler API in System -> Configuration -> Doppler'));
        } else {
            if ($statusCode != '200')
            {
                if ($statusCode == '404' || $statusCode == '4040')
                {
                    Mage::getSingleton('core/session')->addError($this->__('The Doppler API is not currently available, please try later'));
                } else {
                    Mage::getSingleton('core/session')->addError($this->__('Your credentials are not valid, please check your username and API key and try again'));
                    $errorStatusCode = true;
                }
            }
        }

        if($errorStatusCode)
        {
            $this->initAction()
                ->renderLayout();
        } else {

            $id = $this->getRequest()->getParam('id');

            $model = Mage::getModel('makingsense_doppler/leadmap');
            if ($id){
                $model->load($id);

                if (!$model->getId()){
                    $this->_getSession()->addError($this->__('Mapping does not exist'));
                    $this->_redirect('*/*/');
                    return;
                }
            }

            Mage::register('leadmap_data', $model);

            $this->initAction()
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_leadmap_edit'))
                ->renderLayout();
        }

    }

    /**
     * Delete action logic
     */
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id){
            try {
                $model = Mage::getModel('makingsense_doppler/leadmap')->load($id);
                if (!$model->getId()){
                    $this->_getSession()->addError("Leadmap with id '%s' does not exist", $id);
                    $this->_redirect("*/*/");
                    return;
                }

                $model->delete();
                $this->_getSession()->addSuccess($this->__('Leadmap deleted'));
            } catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }

    /**
     * Save action logic
     */
    public function saveAction()
    {

        $data = $this->getRequest()->getPost();

        if ($data){
            try {
                // 1- Validate that there is no attribute already associated with this Doppler field
                $fieldAlreadyExist = false;

                $mappedFields = Mage::getModel('makingsense_doppler/leadmap')->getCollection()->getData();

                $savedDopplerFieldName = '';

                foreach ($mappedFields as $field)
                {
                    $dopplerFieldName = $field['doppler_field_name'];
                    $savedDopplerFieldName = $data['doppler_field_name'];

                    // If editing mapping
                    if (array_key_exists('id', $data)) {
                        if ($field['id'] != $data['id']) {
                            if ($dopplerFieldName == $savedDopplerFieldName) {
                                $fieldAlreadyExist = true;
                            }
                        }
                    // If creating new mapping
                    } else {

                        if ($dopplerFieldName == $savedDopplerFieldName) {
                            $fieldAlreadyExist = true;
                        }
                    }
                }

                if (!$fieldAlreadyExist) {

                    $savedDopplerFieldName = $data['doppler_field_name'];
                    $savedMagentoAttributeCode = $data['magento_field_name'];

                    // Validate if the Magento attribute data type is the same
                    // than the Doppler attribute data type
                    $dopplerFieldDataType = Mage::helper('makingsense_doppler')->getDopplerFieldDataType($savedDopplerFieldName);
                    $magentoAttributeDataType = Mage::helper('makingsense_doppler')->getAttributeDataType($savedMagentoAttributeCode);

                    if ($magentoAttributeDataType != '') {
                        // Since there are less possible attribute types from the Doppler side
                        // Then iterate over the possible Doppler attribute types first
                        $compatibleDataTypes = false;
                        $error = 'Doppler: ' . $dopplerFieldDataType . ' – Magento: ' . $magentoAttributeDataType;

                        switch ($dopplerFieldDataType) {
                            case 'boolean':
                                if ($magentoAttributeDataType == 'bool') {
                                    $compatibleDataTypes = true;
                                }
                                break;
                            case 'number':

                                if ($magentoAttributeDataType == 'varchar' ||
                                    $magentoAttributeDataType == 'varbinary' ||
                                    $magentoAttributeDataType == 'year' ||
                                    $magentoAttributeDataType == 'enum' ||
                                    $magentoAttributeDataType == 'bit' ||
                                    $magentoAttributeDataType == 'tinyint' ||
                                    $magentoAttributeDataType == 'smallint' ||
                                    $magentoAttributeDataType == 'mediumint' ||
                                    $magentoAttributeDataType == 'int' ||
                                    $magentoAttributeDataType == 'bigint' ||
                                    $magentoAttributeDataType == 'float' ||
                                    $magentoAttributeDataType == 'double' ||
                                    $magentoAttributeDataType == 'decimal'
                                ) {
                                    $compatibleDataTypes = true;
                                }
                                break;
                            case 'date':
                                // Format: yyyy-MM-dd
                                if ($magentoAttributeDataType == 'date' ||
                                    $magentoAttributeDataType == 'datetime'
                                ) {
                                    $compatibleDataTypes = true;
                                }
                                break;
                            case 'gender':
                                // M or F
                                if ($savedMagentoAttributeCode == 'gender') {
                                    $compatibleDataTypes = true;
                                    $error = 'Doppler: gender – Magento: ' . $savedMagentoAttributeCode;
                                }

                                break;
                            case 'country':
                                // Country: ISO 3166-1 alpha 2
                                // The country in Magento is a varchar attribute
                                if ($savedMagentoAttributeCode == 'country_id') {
                                    $compatibleDataTypes = true;
                                    $error = 'Doppler: country – Magento: ' . $savedMagentoAttributeCode;
                                }
                                break;
                            default:
                                $compatibleDataTypes = true;
                        }

                        if (!$compatibleDataTypes) {
                            $this->_getSession()->addError($this->__('The Doppler field data type is not compatible with the selected Magento attribute data type: %s', $error));
                            $this->_redirect("*/*/");
                        } else {
                            $model = Mage::getModel('makingsense_doppler/leadmap');
                            $model->setData($data);
                            $model->save();

                            $this->_getSession()->addSuccess($this->__('Saved'));
                        }
                    } else {
                        $model = Mage::getModel('makingsense_doppler/leadmap');
                        $model->setData($data);
                        $model->save();

                        $this->_getSession()->addSuccess($this->__('Saved'));
                    }
                } else {
                    $this->_getSession()->addError($this->__('There is already a Magento attribute associated with the following Doppler field: %s', $savedDopplerFieldName));
                }

            } catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }

    /**
     * MassDelete action logic
     */
    public function massDeleteAction()
    {
        $data = $this->getRequest()->getParam('leadmap');
        if (!is_array($data)){
            $this->_getSession()->addError(
                $this->__("Please select at least one record")
            );
        } else {
            try {
                foreach ($data as $id){
                    $leadmap = Mage::getModel('makingsense_doppler/leadmap')->load($id);
                    $leadmap->delete();
                }

                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted', count($data))
                );
            } catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }
}