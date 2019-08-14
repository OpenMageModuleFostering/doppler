<?php
/**
 * Subscribers admin page controller
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Adminhtml_SubscribersController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Set active menu
     */
    protected function initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('makingsense_doppler/subscribers');

        return $this;
    }

    /**
     * Load current customer
     */
    protected function _initCustomer($idFieldName = 'id')
    {
        $this->_title($this->__('Customers'))->_title($this->__('Manage Subscribers'));

        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }

    /**
     * Customers list action
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

            $usernameValue = Mage::getStoreConfig('doppler/connection/username');
            $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

            if($usernameValue != '' && $apiKeyValue != '')
            {
                // Before loading the page, get all the import tasks that are processing to check if they are completed now
                $pendingImportTasks = Mage::getModel('makingsense_doppler/importtasks')
                                                    ->getCollection()
                                                    ->addFieldToFilter('status', array('eq' => 'Processing'));

                if (count($pendingImportTasks))
                {
                    foreach ($pendingImportTasks as $pendingImportTask)
                    {
                        $importTaskTime = $pendingImportTask->getCreation();
                        $format = Mage::app()->getLocale()->getDateFormat(
                            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
                        );
                        Mage::getSingleton('adminhtml/session')->addNotice($this->__('The export task that has been started at %s is in progress', Mage::helper('core')->formatTime($importTaskTime, 'long', true)));

                        $importTaskId = $pendingImportTask->getData('id');
                        $importId = $pendingImportTask->getImportId();

                        // Get cURL resource
                        $ch = curl_init();

                        // Set url
                        curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/tasks/' . $importId . '/import-errors');

                        // Set method
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

                        // Set options
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        // Set headers
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: token ' . $apiKeyValue,
                            ]
                        );

                        // Send the request & save response to $resp
                        $resp = curl_exec($ch);

                        if ($resp)
                        {
                            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            if ($statusCode == '200')
                            {
                                $responseContent = json_decode($resp, true);
                                $numberOfErrors = $responseContent['itemsCount'];

                                $serializedCustomersEmailsFromImport = $pendingImportTask->getCustomers();
                                $customersEmailsFromImport = unserialize($serializedCustomersEmailsFromImport);

                                // If there are import errors, then get the emails from the emails with errors
                                if ($numberOfErrors > 0)
                                {
                                    $itemsWithErrors = $responseContent['items'];

                                    foreach ($customersEmailsFromImport as $customerEmail)
                                    {
                                        // Load this customer account for all the websites
                                        $customerFromAllWebsites = Mage::getModel('customer/customer')
                                                                        ->getCollection()
                                                                        ->addAttributeToSelect('*')
                                                                        ->addAttributeToFilter('email', $customerEmail);

                                        foreach ($customerFromAllWebsites as $customer)
                                        {
                                            $errorInExport = false;
                                            foreach ($itemsWithErrors as $item)
                                            {
                                                if (isset($item['email']))
                                                {
                                                    if ($item['email'] == $customerEmail)
                                                    {
                                                        $errorInExport = true;
                                                        if (isset($item['errorCode']))
                                                        {
                                                            $customer->setStatusDopplerSync($item['errorCode']);
                                                        } else {
                                                            $customer->setStatusDopplerSync('Error');
                                                        }
                                                        $customer->save();
                                                    }
                                                }
                                            }
                                            if (!$errorInExport)
                                            {
                                                $customer->setStatusDopplerSync('Completed');
                                                $customer->save();
                                            }
                                        }

                                    }

                                } else {

                                    // If there are no errors, then iterate over all customrs and set status 'Completed'
                                    foreach ($customersEmailsFromImport as $customerEmail)
                                    {
                                        // Load this customer account for all the websites
                                        $customerFromAllWebsites = Mage::getModel('customer/customer')
                                            ->getCollection()
                                            ->addAttributeToSelect('*')
                                            ->addAttributeToFilter('email', $customerEmail);

                                        foreach ($customerFromAllWebsites as $customer)
                                        {
                                            $customer->setStatusDopplerSync('Completed');
                                            $customer->save();
                                        }
                                    }
                                }

                                $importTask = Mage::getModel('makingsense_doppler/importtasks')->load($importTaskId);
                                $importTask->setStatus('Completed');
                                $importTask->save();

                                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The export task that has been started at %s has finished', Mage::helper('core')->formatTime($importTaskTime, 'long', true)));

                            }
                        }

                        // Close request to clear up some resources
                        curl_close($ch);
                    }
                }

            }

            $this->_title($this->__('Customers'))->_title($this->__('Manage Subscribers'));

            if ($this->getRequest()->getQuery('ajax')) {
                $this->_forward('grid');
                return;
            }
            $this->loadLayout();

            /**
             * Set active menu item
             */
            $this->_setActiveMenu('doppler/subscribers');

            /**
             * Append customers block to content
             */
            $this->_addContent(
                $this->getLayout()->createBlock('makingsense_doppler/adminhtml_subscribers', 'subscribers')
            );

            /**
             * Add breadcrumb item
             */
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Customers'), Mage::helper('adminhtml')->__('Customers'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Manage Subscribers'), Mage::helper('adminhtml')->__('Manage Subscribers'));

            $this->renderLayout();
        }
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('makingsense_doppler/adminhtml_subscribers_grid')->toHtml()
        );
    }

    /**
     * Customer edit action
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
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_leadmap_edit'))
                ->renderLayout();
        } else {

            $id = $this->getRequest()->getParam('id');

            $model = Mage::getModel('customer/customer');
            if ($id){
                $model->load($id);

                if (!$model->getId()){
                    $this->_getSession()->addError($this->__('Mapping does not exist'));
                    $this->_redirect('*/*/');
                    return;
                }
            }

            Mage::register('subscribers_data', $model);

            $this->initAction()
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_subscribers_edit'))
                ->renderLayout();
        }
    }

    /**
     * Create new customer action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Delete customer action
     */
    public function deleteAction()
    {
        $this->_initCustomer();
        $customer = Mage::registry('current_customer');
        if ($customer->getId()) {
            try {
                $customer->load($customer->getId());
                $customer->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The customer has been deleted.'));
            }
            catch (Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/customer');
    }

    /**
     * Save customer action
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();

        if ($data) {
            try {
                // Load customer
                $customer = Mage::getModel('customer/customer');
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $customer->load($data['entity_id']);

                // Export customer to Doppler
                $dopplerMappedFields = Mage::helper('makingsense_doppler')->getDopplerMappedFields();
                $mappedFieldsCount = count($dopplerMappedFields);
                $leadMappingArrayKeys = array_keys($dopplerMappedFields);
                $dopplerAttributeTypes = array();
                for ($i = 0; $i < $mappedFieldsCount; $i++) {
                    $fieldName = $leadMappingArrayKeys[$i];
                    $dopplerAttributeTypes[$fieldName] = Mage::helper('makingsense_doppler')->getDopplerFieldDataType($fieldName);
                }
                $exportError = Mage::helper('makingsense_doppler')->exportCustomerToDoppler($customer, $data['doppler_list'], $dopplerMappedFields, $dopplerAttributeTypes);

                if (!$exportError)
                {
                    Mage::getSingleton('core/session')->addSuccess($this->__('The customer has been subscribed to the selected list'));
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'customers.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/customer_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'customers.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/customer_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Prepare file download response
     *
     * @todo remove in 1.3
     * @deprecated please use $this->_prepareDownloadResponse()
     * @see Mage_Adminhtml_Controller_Action::_prepareDownloadResponse()
     * @param string $fileName
     * @param string $content
     * @param string $contentType
     */
    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $this->_prepareDownloadResponse($fileName, $content, $contentType);
    }

    /**
     * MassExport action logic
     */
    public function massExportAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select customer(s).'));

        } else {
            try {
                $customers =  Mage::getModel('customer/customer')->getCollection()
                    ->addFieldToFilter('entity_id', array('in' => $customersIds));

                $dopplerListId = $this->getRequest()->getParam('list');
                Mage::helper('makingsense_doppler')->exportMultipleCustomersToDoppler($customers, $dopplerListId);

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Validate admin user permissions
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('customer/manage');
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data['account'] = $this->_filterDates($data['account'], array('dob'));
        return $data;
    }
}