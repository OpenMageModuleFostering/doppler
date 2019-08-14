<?php
/**
 * Lists admin page controller
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Adminhtml_ListsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Set active menu
     */
    protected function initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('makingsense_doppler/lists');

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

            $usernameValue = Mage::getStoreConfig('doppler/connection/username');
            $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

            if($usernameValue != '' && $apiKeyValue != '')
            {
                // Get cURL resource
                $ch = curl_init();

                // Set url
                curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists?page=1&per_page=200');

                // Set method
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

                // Set options
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                // Set headers
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: token " . $apiKeyValue,
                    ]
                );

                // Send the request & save response to $resp
                $resp = curl_exec($ch);

                if ($resp)
                {
                    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    $responseContent = json_decode($resp, true);

                    if ($statusCode == '200') {

                        $model = Mage::getModel('makingsense_doppler/lists');

                        // First, remove the old Doppler lists
                        foreach ($model->getCollection() as $list)
                        {
                            $model->load($list->getId())->delete();
                        }

                        // Then, store all list from latest API call
                        $fieldsResponseArray = $responseContent['items'];

                        foreach ($fieldsResponseArray as $field) {
                            $data = array();


                            $data['name'] = $field['name'];
                            $data['list_id'] = $field['listId'];
                            $data['status'] = $field['currentStatus'];
                            $data['subscribers_count'] = $field['subscribersCount'];
                            $data['creation_date'] = $field['creationDate'];

                            $model->setData($data);
                            $model->save();
                        }

                    } else
                    {
                        $this->_getSession()->addError($this->__('The following errors occurred creating your list: %s', $responseContent['title']));
                    }
                }

                // Close request to clear up some resources
                curl_close($ch);
            }

            $this->initAction()
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_lists'))
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
        $id = $this->getRequest()->getParam('id');

        $model = Mage::getModel('makingsense_doppler/lists');
        if ($id){
            $model->load($id);

            if (!$model->getId()){
                $this->_getSession()->addError($this->__('List does not exist'));
                $this->_redirect('*/*/');
                return;
            }
        }

        Mage::register('lists_data', $model);

        $this->initAction()
            ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_lists_edit'))
            ->renderLayout();
    }

    /**
     * Delete action logic
     */
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id){
            try {
                $list = Mage::getModel('makingsense_doppler/lists')->load($id);
                if (!$list->getId()){
                    $this->_getSession()->addError("List %s does not exist", $id);
                    $this->_redirect("*/*/");
                    return;
                }

                $usernameValue = Mage::getStoreConfig('doppler/connection/username');
                $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

                if($usernameValue != '' && $apiKeyValue != '') {
                    // Get cURL resource
                    $ch = curl_init();

                    // Set url
                    curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/lists/' . $list->getListId());

                    // Set method
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

                    // Set options
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                    // Set headers
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: token " . $apiKeyValue,
                            "Content-Type: application/json",
                        ]
                    );


                    // Send the request & save response to $resp
                    $resp = curl_exec($ch);

                    if($resp)
                    {
                        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                        if ($statusCode == '200')
                        {
                            $this->_getSession()->addSuccess($this->__("The list '%s' has been successfully removed", $list->getName()));
                        } else
                        {
                            $responseContent = json_decode($resp, true);
                            $this->_getSession()->addError($this->__('The following errors occurred removing your list: ' . $responseContent['title']));
                        }
                    }

                    // Close request to clear up some resources
                    curl_close($ch);

                    $list->delete();
                }

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

        if ($data)
        {
            // If we are editing a list, then save the new list name and check if the edited list should be the default list
            if (array_key_exists('id', $data))
            {
                try {
                    $usernameValue = Mage::getStoreConfig('doppler/connection/username');
                    $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

                    if($usernameValue != '' && $apiKeyValue != '')
                    {
                        // Get cURL resource
                        $ch = curl_init();

                        // Set url
                        curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/lists/' . $data['list_id']);

                        // Set method
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

                        // Set options
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        // Set headers
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: token " . $apiKeyValue,
                                "Content-Type: application/json",
                            ]
                        );

                        // Create body
                        $body = '{ name: "' . $data['name'] . '" }';

                        // Set body
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

                        // Send the request & save response to $resp
                        $resp = curl_exec($ch);

                        if ($resp)
                        {
                            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            if ($statusCode == '200')
                            {
                                $this->_getSession()->addSuccess($this->__('The changes have been saved'));

                                // If the new list has been marked as default, then update default list in Magento
                                if (isset($data['default_list']))
                                {
                                    $model = Mage::getModel('makingsense_doppler/defaultlist');

                                    // Remove the old list information
                                    foreach ($model->getCollection() as $list) {
                                        $model->load($list->getId())->delete();
                                    }

                                    $newDefaultList = array();

                                    $newDefaultList['name'] = $data['name'];
                                    $newDefaultList['listId'] = $data['list_id'];

                                    $model->setData($newDefaultList);
                                    $model->save();
                                } else {
                                    // If not, check if the current list was the default list and remove it if that was the case
                                    $isDefaultList = Mage::helper('makingsense_doppler')->isDefaultList($data['id']);

                                    if ($isDefaultList)
                                    {
                                        $model = Mage::getModel('makingsense_doppler/defaultlist');
                                        foreach ($model->getCollection() as $list) {
                                            $model->load($list->getId())->delete();
                                        }
                                    }
                                }
                            } else
                            {
                                $responseContent = json_decode($resp, true);
                                $this->_getSession()->addError($this->__('The following errors occurred creating your list: ' . $responseContent['title']));
                            }
                        }

                        // Close request to clear up some resources
                        curl_close($ch);
                    }

                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
            // Else, save the new list
            else
            {
                try
                {

                    $usernameValue = Mage::getStoreConfig('doppler/connection/username');
                    $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

                    if($usernameValue != '' && $apiKeyValue != '')
                    {
                        // Get cURL resource
                        $ch = curl_init();

                        // Set url
                        curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists');

                        // Set method
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

                        // Set options
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        // Set headers
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: token " . $apiKeyValue,
                                "Content-Type: application/json",
                            ]
                        );

                        // Create body
                        $body = '{ name: "' . $data['name'] . '" }';

                        // Set body
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

                        // Send the request & save response to $resp
                        $resp = curl_exec($ch);

                        if ($resp)
                        {
                            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            if ($statusCode == '201')
                            {
                                $this->_getSession()->addSuccess($this->__('The list has been successfully created'));

                                // If the new list has been marked as default, then update default list in Magento
                                if (isset($data['default_list']))
                                {
                                    $responseContent = json_decode($resp, true);
                                    $createdListId = $responseContent['createdResourceId'];

                                    $model = Mage::getModel('makingsense_doppler/defaultlist');

                                    // Remove the old list information
                                    foreach ($model->getCollection() as $list)
                                    {
                                        $model->load($list->getId())->delete();
                                    }

                                    $newDefaultList = array();

                                    $newDefaultList['name'] = $data['name'];
                                    $newDefaultList['listId'] = $createdListId;

                                    $model->setData($newDefaultList);
                                    $model->save();
                                }
                            } else {
                                $responseContent = json_decode($resp, true);
                                $this->_getSession()->addError($this->__('The following errors occurred creating your list: ' . $responseContent['title']));
                            }
                        }

                        // Close request to clear up some resources
                        curl_close($ch);
                    }

                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }

        $this->_redirect("*/*/");
    }

    /**
     * MassDelete action logic
     */
    public function massDeleteAction()
    {
        $data = $this->getRequest()->getParam('lists');
        if (!is_array($data))
        {
            $this->_getSession()->addError(
                $this->__("Please select at least one record")
            );
        } else
        {
            try
            {
                foreach ($data as $id)
                {
                    $list = Mage::getModel('makingsense_doppler/lists')->load($id);

                    $usernameValue = Mage::getStoreConfig('doppler/connection/username');
                    $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

                    if($usernameValue != '' && $apiKeyValue != '')
                    {
                        // Get cURL resource
                        $ch = curl_init();

                        // Set url
                        curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists/' . $list->getListId());

                        // Set method
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

                        // Set options
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        // Set headers
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: token " . $apiKeyValue,
                                "Content-Type: application/json",
                            ]
                        );

                        // Send the request & save response to $resp
                        $resp = curl_exec($ch);

                        if ($resp)
                        {
                            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            if ($statusCode == '200')
                            {
                                $this->_getSession()->addSuccess($this->__("The list '%s' has been successfully removed", $list->getName()));
                            } else {
                                $responseContent = json_decode($resp, true);
                                $this->_getSession()->addError($this->__('The following errors occurred removing your list: ' . $responseContent['title']));
                            }
                        }

                        // Close request to clear up some resources
                        curl_close($ch);

                        $list->delete();
                    }
                }
            } catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }
}