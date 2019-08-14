<?php
/**
 * Defaultlist admin page controller
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Adminhtml_DefaultlistController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Set active menu
     */
    protected function initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('makingsense_doppler/defaultlist');

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

            // If there is a default list, then validate its status is Doppler
            $defaultListCollection = Mage::getModel('makingsense_doppler/defaultlist')->getCollection();

            if ($defaultListCollection->getData() > 0) {
                $listId = 0;

                foreach ($defaultListCollection as $defaultList)
                {
                    $listId = $defaultList->getData('listId');
                }

                if ($listId != 0)
                {
                    $usernameValue = Mage::getStoreConfig('doppler/connection/username');
                    $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

                    if($usernameValue != '' && $apiKeyValue != '')
                    {
                        // Get cURL resource
                        $ch = curl_init();

                        // Set url
                        curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists/' . $listId);

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

                            if ($statusCode == '200')
                            {
                                $model = Mage::getModel('makingsense_doppler/defaultlist');

                                // Remove the old list information
                                foreach ($model->getCollection() as $list) {
                                    $model->load($list->getId())->delete();
                                }

                                $data['name'] = $responseContent['name'];
                                $data['listId'] = $responseContent['listId'];

                                // If the list has been deleted, then let the user know about that
                                if ($responseContent['currentStatus'] == MakingSense_Doppler_Helper_Data::DOPPLER_LIST_STATUS_DELETED) {
                                    $data['list_status'] = MakingSense_Doppler_Helper_Data::DOPPLER_LIST_STATUS_DELETED;
                                } else {
                                    $data['list_status'] = MakingSense_Doppler_Helper_Data::DOPPLER_LIST_STATUS_ENABLED;
                                }

                                $model->setData($data);
                                $model->save();
                            } else {
                                $this->_getSession()->addError($this->__('The following errors occurred retrieving your default list: %s', $responseContent['title']));
                            }
                        }

                        // Close request to clear up some resources
                        curl_close($ch);
                    }
                }
            }

            $this->initAction()
                ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_defaultlist'))
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

        $model = Mage::getModel('makingsense_doppler/defaultlist');
        if ($id){
            $model->load($id);

            if (!$model->getId()){
                $this->_getSession()->addError($this->__('Default list does not exist'));
                $this->_redirect('*/*/');
                return;
            }
        }

        Mage::register('defaultlist_data', $model);

        $this->initAction()
            ->_addContent($this->getLayout()->createBlock('makingsense_doppler/adminhtml_defaultlist_edit'))
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
                $list = Mage::getModel('makingsense_doppler/defaultlist')->load($id);
                if (!$list->getId()){
                    $this->_getSession()->addError("Default list %s does not exist", $id);
                    $this->_redirect("*/*/");
                    return;
                }

                $list->delete();

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
                $model = Mage::getModel('makingsense_doppler/defaultlist');

                // First, remove the previous default list
                foreach ($model->getCollection() as $list) {
                    $model->load($list->getId())->delete();
                }

                // Then save the new default list
                $listData = array();
                $listData['listId'] = $data['doppler_list_id'];

                $model->setData($listData);
                $model->save();

                $this->_getSession()->addSuccess($this->__('Saved'));
            } catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect("*/*/");
    }


}