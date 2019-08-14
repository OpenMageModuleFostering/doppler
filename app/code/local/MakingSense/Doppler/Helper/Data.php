<?php
/**
 * Module data helper
 *
 * @category    MakingSense
 * @package     Doppler
 
 */

class MakingSense_Doppler_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Doppler lead mapping array
     *
     * @var null|array
     */
    protected $_leadMapping = null;

    /**
     * Customer attributes from mapped fields
     *
     * @var null|array
     */
    protected $_customerAttributes = null;

    /**
     * Doppler fields array
     *
     * @var null|array
     */
    protected $_fieldsArray = null;

    /**
     * Doppler lists array
     *
     * @var null|array
     */
    protected $_listsArray = null;

    /**
    * Doppler list statuses
    */
    const DOPPLER_LIST_STATUS_DELETED = 'deleted';
    const DOPPLER_LIST_STATUS_ENABLED = 'enabled';

    /**
     * API call to test if Doppler API is active
     */
    public function testAPIConnection()
    {
        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        // API not available error code
        $statusCode = '4040';

        if($usernameValue != '' && $apiKeyValue != '')
        {
            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists');

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

            if($resp) {
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            // Close request to clear up some resources
            curl_close($ch);
        } else {
            $statusCode = false;
        }

        return $statusCode;
    }

    /**
     * Export Magento customer to Doppler
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param int $dopplerListId
     *
     * @return bool $errorOnExport
     */
    public function exportCustomerToDoppler($customer, $dopplerListId, $dopplerMappedFields, $dopplerFieldsDataType)
    {
        $errorOnExport = true;

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '')
        {
            // Load customer address
            $customerAddressData = array();
            foreach ($customer->getAddresses() as $address)
            {
                $customerAddressData = $address->toArray();
            }

            // Load Magento customer attributes from mapped fields
            foreach ($dopplerMappedFields as $field)
            {
                // Cleanup $field value
                $trimmedFieldValue = trim($field);

                // Get data from customer attribute
                $customerData =  $customer->getData($trimmedFieldValue);

                // If the customer data is empty, then it's probably a customer address attribute
                if (!$customerData)
                {
                    $customerAddressFound = false;
                    foreach ($customerAddressData as $magentoAttribute => $value)
                    {
                        if ($magentoAttribute == $trimmedFieldValue)
                        {
                            $addressData = $customerAddressData[$trimmedFieldValue];
                            $this->_customerAttributes[$trimmedFieldValue] = $addressData;
                            $customerAddressFound = true;
                        }
                    }
                    if (!$customerAddressFound)
                    {
                        $this->_customerAttributes[$trimmedFieldValue] = $customerData;
                    }
                } else {
                    $this->_customerAttributes[$trimmedFieldValue] = $customerData;
                }

            }

            /* Sample body format for API (add subscriber to list)
             * {"email": "eeef1cba-0718-4b18-b68f-5e56adaa08b9@mailinator.com",
                "fields": [ {name: "FIRSTNAME", value: "First Name"},
                            {name: "LASTNAME", value: "Last Name"},
                            {name: "GENDER", value: "N"},
                            {name: "BIRTHDAY", value: "N"}]}
            */

            // Create body
            $body = '{ "email": "' . $customer->getEmail() . '", ';
            $body .= ' "fields": [ ';

                $mappedFieldsCount = count($dopplerMappedFields);
                $leadMappingArrayKeys = array_keys($dopplerMappedFields);
                $customerAttributesArrayKeys = array_keys($this->_customerAttributes);

                for ($i = 0; $i < $mappedFieldsCount; $i++)
                {
                    $fieldName = $leadMappingArrayKeys[$i];
                    $customerAttributeValue = $this->_customerAttributes[$customerAttributesArrayKeys[$i]];

                    // Validate each mapped field before exporting
                    $dopplerFieldDataType = $dopplerFieldsDataType[$fieldName];

                    switch ($dopplerFieldDataType) {
                        case 'date':
                            // Format: yyyy-MM-dd
                            if ($dopplerFieldDataType == 'date' ||
                                $dopplerFieldDataType == 'datetime'
                            ) {
                                $customerAttributeValue = self::getFormattedDate($customerAttributeValue);
                            }
                            break;
                        case 'gender':
                            // M or F
                            // Magento saves 1 for Male and 2 for Female
                            // Conver that to M for Male and F for Female
                            if ($customerAttributesArrayKeys[$i] == 'gender')
                            {
                                if ($customerAttributeValue == 1)
                                {
                                    $customerAttributeValue = 'M';
                                } else if ($customerAttributeValue == 2)
                                {
                                    $customerAttributeValue = 'F';
                                }
                            }

                            break;
                        case 'country':
                            // Country: ISO 3166-1 alpha 2
                            // Check if attribute is 'country', if not return false
                            // Magento already stores the country in ISO 3166-1 alpha 2
                            // No conversion is necessary
                            break;
                        default:
                    }

                    $body .= '{ name: "' . $fieldName . '", value: "' . $customerAttributeValue . '" }, ';
                }

            $body .= ']}';

            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists/' . $dopplerListId . '/subscribers');

            // Set method
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            // Set options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Set headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token " . $apiKeyValue,
                "Content-Type: application/json",
            ]);

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
                    // Update 'status_doppler_sync' to 'Completed' since the customer was successfully exported
                    if($customer->getId() > 1){
                        $customer->setStatusDopplerSync('Completed');
                        $customer->save();
                    }

                    $errorOnExport = false;
                } else {
                    // There has been an error when trying to export the customer to Doppler
                    // Then process the error message
                    $responseContent = json_decode($resp, true);

                    Mage::log($responseContent, null, 'error-response.log');

                    // If the response contains the 'errorCode' item, then get error code
                    $errorCode = $responseContent['errorCode'];

                    if ($statusCode == 400)
                    {
                        // Update 'status_doppler_sync' to the error code since the customer was not successfully exported but we have the error code
                        if ($errorCode == 1)
                        {
                            $customer->setStatusDopplerSync('Validation Error');
                        } elseif ($errorCode == 13) {
                            $customer->setStatusDopplerSync('Invalid Field Values');
                        } elseif ($errorCode == 4) {
                            $customer->setStatusDopplerSync('Invalid Email');
                        } elseif ($errorCode == 8) {
                            $customer->setStatusDopplerSync('Duplicated');
                        } elseif ($errorCode == 9) {
                            $customer->setStatusDopplerSync('Unsubscribed by User');
                        }
                        $customer->save();

                    } else {
                        // Update 'status_doppler_sync' to 'Error' since the customer was not successfully exported but we don't have the error code
                        $customer->setStatusDopplerSync('Error');
                        $customer->save();
                    }

                    // If the response contains the 'error' item, then it's a validation error
                    if (in_array('error', $responseContent))
                    {
                        $errorResponseArray = $responseContent['error'];

                        foreach ($errorResponseArray as $field)
                        {
                            $fieldName = $field['fieldName'];
                            $invalidValue = $field['invalidValue'];

                            $validationError = $this->__('The field "%s" is not compatible with the associated attribute value for the customer: %s', $fieldName, $invalidValue);
                            Mage::getSingleton('adminhtml/session')->addError($validationError);
                        }
                    } else {
                        $errorDetail = $responseContent['detail'];

                        $exportCustomerError = $this->__('The following error ocurred on the customer export: %s', $errorDetail);
                        Mage::getSingleton('adminhtml/session')->addError($exportCustomerError);
                    }

                }
            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return $errorOnExport;
    }

    /**
     * Export multiple Magento customers to Doppler
     *
     * @param $customers
     * @param $dopplerListId
     *
     * @return bool $errorOnExport
     */
    public function exportMultipleCustomersToDoppler($customers, $dopplerListId)
    {
        $errorOnExport = true;

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '')
        {
            $dopplerMappedFields = Mage::helper('makingsense_doppler')->getDopplerMappedFields();

            // Create body
            $body = '{ "fields": [ ';

            $mappedFieldsCount = count($dopplerMappedFields);
            $leadMappingArrayKeys = array_keys($dopplerMappedFields);

            $dopplerFieldsDataType = array();
            for ($i = 0; $i < $mappedFieldsCount; $i++) {
                $fieldName = $leadMappingArrayKeys[$i];
                $dopplerFieldsDataType[$fieldName] = Mage::helper('makingsense_doppler')->getDopplerFieldDataType($fieldName);
            }

            // Get list of mapped fields
            for ($i = 0; $i < $mappedFieldsCount; $i++)
            {
                $fieldName = $leadMappingArrayKeys[$i];
                $body .= '"' . $fieldName . '"';

                if (($i + 1) < $mappedFieldsCount)
                {
                    $body .= ',';
                }
            }

            $body .= '],';

            $body .= '"items": [ ';

            $customerCount = $customers->getSize();

            $customerCounter = 1;
            foreach ($customers as $customer)
            {
                // Load customer address
                $customerAddressData = array();
                foreach ($customer->getAddresses() as $address)
                {
                    $customerAddressData = $address->toArray();
                }

                // Load Magento customer attributes from mapped fields
                foreach ($dopplerMappedFields as $field)
                {
                    // Cleanup $field value
                    $trimmedFieldValue = trim($field);

                    // Load customer
                    $customer = Mage::getModel('customer/customer')->load($customer->getId());

                    // Get data from customer attribute
                    $customerData =  $customer->getData($trimmedFieldValue);

                    // If the customer data is empty, then it's probably a customer address attribute
                    if (!$customerData)
                    {
                        $customerAddressFound = false;
                        foreach ($customerAddressData as $magentoAttribute => $value)
                        {
                            if ($magentoAttribute == $trimmedFieldValue)
                            {
                                $addressData = $customerAddressData[$trimmedFieldValue];
                                $this->_customerAttributes[$trimmedFieldValue] = $addressData;
                                $customerAddressFound = true;
                            }
                        }
                        if (!$customerAddressFound)
                        {
                            $this->_customerAttributes[$trimmedFieldValue] = $customerData;
                        }
                    } else {
                        $this->_customerAttributes[$trimmedFieldValue] = $customerData;
                    }

                }

                Mage::log($this->_customerAttributes, null,'customer-attributes.log');

                $body .= '{ "email": "' . $customer->getEmail() . '", ';

                $body .= ' "fields": [ ';

                $customerAttributesArrayKeys = array_keys($this->_customerAttributes);

                for ($i = 0; $i < $mappedFieldsCount; $i++)
                {
                    $fieldName = $leadMappingArrayKeys[$i];
                    $customerAttributeValue = $this->_customerAttributes[$customerAttributesArrayKeys[$i]];

                    // Validate each mapped field before exporting
                    $dopplerFieldDataType = $dopplerFieldsDataType[$fieldName];

                    switch ($dopplerFieldDataType) {
                        case 'date':
                            // Format: yyyy-MM-dd
                            if ($dopplerFieldDataType == 'date' ||
                                $dopplerFieldDataType == 'datetime'
                            ) {
                                $customerAttributeValue = self::getFormattedDate($customerAttributeValue);
                            }
                            break;
                        case 'gender':
                            // M or F
                            // Magento saves 1 for Male and 2 for Female
                            // Conver that to M for Male and F for Female
                            if ($customerAttributesArrayKeys[$i] == 'gender')
                            {
                                if ($customerAttributeValue == 1)
                                {
                                    $customerAttributeValue = 'M';
                                } else if ($customerAttributeValue == 2)
                                {
                                    $customerAttributeValue = 'F';
                                }
                            }

                            break;
                        case 'country':
                            // Country: ISO 3166-1 alpha 2
                            // Check if attribute is 'country', if not return false
                            // Magento already stores the country in ISO 3166-1 alpha 2
                            // No conversion is necessary
                            break;
                        default:
                    }

                    $body .= '{ "name": "' . $fieldName . '", "value": "' . $customerAttributeValue . '"';

                    if (($i + 1) < $mappedFieldsCount)
                    {
                        $body .= '},';
                    } else {
                        $body .= '}';
                    }
                }

                if ($customerCounter == $customerCount)
                {
                    $body .= ']}';
                } else {
                    $body .= ']},';
                }

                $customerCounter++;

            }

            $body .= '],}}';

            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue . '/lists/' . $dopplerListId . '/subscribers/import');

            // Set method
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            // Set options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Set headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token " . $apiKeyValue,
                "Content-Type: application/json",
            ]);

            // Set body
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            // Send the request & save response to $resp
            $resp = curl_exec($ch);

            if ($resp)
            {
                // There has been an error when trying to export the customer to Doppler
                // Then process the error message
                $responseContent = json_decode($resp, true);

                // If the response contains the 'error' item, then it's a validation error
                if (in_array('error', $responseContent))
                {
                    $errorResponseArray = $responseContent['error'];

                    foreach ($errorResponseArray as $field)
                    {
                        $fieldName = $field['fieldName'];
                        $invalidValue = $field['invalidValue'];

                        $validationError = $this->__('The field "%s" is not compatible with the associated attribute value for the customer: %s', $fieldName, $invalidValue);
                        Mage::getSingleton('adminhtml/session')->addError($validationError);
                    }
                } else {
                    $customerEmails = array();

                    // If the import task was successfully created, then update the customer attribute to let the user know
                    // that the customer is in process of being imported on Doppler
                    foreach ($customers as $customer)
                    {
                        $customer->setStatusDopplerSync('Processing');
                        $customer->save();

                        $customerEmails[] = $customer->getEmail();
                    }

                    // Prepare customers to save on the import task in the Magento 'doppler_importtasks' table
                    $encodedCustomersEmails = serialize($customerEmails);

                    // Save import task ID from Doppler into the 'doppler_importtasks' table
                    $importTaskId = $responseContent['createdResourceId'];
                    $importTaskStatus = 'Processing';
                    $importTask = Mage::getModel('makingsense_doppler/importtasks');
                    $importTask->setImportId($importTaskId);
                    $importTask->setStatus($importTaskStatus);
                    $importTask->setCustomers($encodedCustomersEmails);
                    $currentDateTime = Varien_Date::now();
                    Mage::log($currentDateTime, null,'$currentDateTime.log');
                    $importTask->setCreation($currentDateTime);
                    $importTask->save();

                    // Return the notice message informing the admin that the import is in progress
                    $importMessage = $responseContent['message'];
                    $exportCustomerError = $importMessage;
                    Mage::getSingleton('adminhtml/session')->addNotice($this->__($exportCustomerError));
                }

            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return $errorOnExport;
    }

    public function getDopplerMappedFields() {

        // Get Doppler mapped fields from Magento
        $leadmapCollection = Mage::getModel('makingsense_doppler/leadmap')->getCollection();

        foreach ($leadmapCollection->getData() as $leadmap)
        {
            $this->_leadMapping[$leadmap['doppler_field_name']] = $leadmap['magento_field_name'];
        }

        return $this->_leadMapping;
    }

    /**
     * Get all fields from Doppler
     *
     * @return array
     */
    public function getDopplerFields()
    {
        $this->_fieldsArray = array();

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '') {
            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/fields');

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

            if($resp)
            {
                $responseContent = json_decode($resp, true);
                $fieldsResponseArray = $responseContent['items'];

                foreach ($fieldsResponseArray as $field)
                {
                    $fieldName = $field['name'];

                    // The 'EMAIL' field shouldn't be available since it's read-only in Doppler
                    if ($fieldName != 'EMAIL')
                    {
                        $this->_fieldsArray[$fieldName] = $fieldName;
                    }
                }
            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return $this->_fieldsArray;
    }

    /**
     * Get all fields from Doppler with their data type
     *
     * @return array
     */
    public function getDopplerFieldsWithDataType()
    {
        $this->_fieldsArray = array();

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '') {
            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/fields');

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

            if($resp)
            {
                $responseContent = json_decode($resp, true);
                $fieldsResponseArray = $responseContent['items'];

                foreach ($fieldsResponseArray as $field)
                {
                    $fieldName = $field['name'];
                    $fieldDataType = $field['type'];
                    $this->_fieldsArray[$fieldName] = $fieldDataType;
                }
            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return $this->_fieldsArray;
    }

    /**
     * Get Doppler lists from API
     *
     * @return array
     */
    public function getDopplerLists()
    {
        $this->_listsArray = array();

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '') {
            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/lists?page=1&per_page=200');

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

            if($resp)
            {
                $responseContent = json_decode($resp, true);
                $listsResponseArray = $responseContent['items'];

                foreach ($listsResponseArray as $list)
                {
                    $fieldName = $list['name'];
                    $listId = $list['listId'];
                    $this->_listsArray[$listId] = $fieldName;
                }

            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return $this->_listsArray;
    }

    /**
     * Get default Doppler list from Magento
     *
     * @return int $defaultDopplerList
     */
    public function getDefaultDopplerList() {
        $defaultDopplerList = 0;
        $defaultListCollection = Mage::getModel('makingsense_doppler/defaultlist')->getCollection();

        foreach ($defaultListCollection->getData() as $defaultList)
        {
            $listStatus = $defaultList['list_status'];

            if ($listStatus == self::DOPPLER_LIST_STATUS_ENABLED) {
                $defaultDopplerList = $defaultList['listId'];
            }
        }

        return $defaultDopplerList;
    }

    /**
     * Get default Doppler list status
     *
     * @return bool
     */
    public function isDefaultListEnabled() {
        $defaultListCollection = Mage::getModel('makingsense_doppler/defaultlist')->getCollection();

        foreach ($defaultListCollection->getData() as $defaultList)
        {
            $listStatus = $defaultList['list_status'];

            if ($listStatus == self::DOPPLER_LIST_STATUS_ENABLED) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if Doppler list is the default list
     *
     * @param int $listId
     * @return int
     */
    public function isDefaultList($listId)
    {
        if ($listId)
        {
            $list = Mage::getModel('makingsense_doppler/lists')->load($listId);
            $dopplerListId = $list->getListId();

            $defaultListCollection = Mage::getModel('makingsense_doppler/defaultlist')->getCollection();
            foreach ($defaultListCollection as $defaultList)
            {
                if ($dopplerListId == $defaultList->getData('listId'))
                {
                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Get attribute data type
     * The attribute could be a customer attribute
     * or customer address attribute
     *
     * Possible returned data types:
     * - text
     * - varchar
     * - int
     * - static
     * - datetime
     * - decimal
     *
     * - In case of being static, it could be:
     *  varchar
     *  varbinary
     *  char
     *  date
     *  datetime
     *  timestamp
     *  time
     *  year
     *  enum
     *  set
     *  bit
     *  bool
     *  tinyint
     *  smallint
     *  mediumint
     *  int
     *  bigint
     *  float
     *  double
     *  decimal
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeDataType($attributeCode)
    {
        // Get customer attributes
        $attributes = Mage::getModel('customer/customer')->getAttributes();
        $customerAttributesArray = array();
        foreach ($attributes as $attribute) {
            if (($label = $attribute->getName()))
            {
                $customerAttributesArray[$label] = $label;
            }
        }

        // Check if attribute is a customer attribute
        $isCustomerAttribute = false;
        if (in_array($attributeCode, $customerAttributesArray))
        {
            // If customer attribute, then load attribute
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('customer', $attributeCode);
            $attributeDataType = $attribute->getData('backend_type');

            // If the attribute data type is static, then we have to load the table and check the DATA_TYPE column
            // to see what is the data type for that attribute
            if ($attributeDataType == 'static')
            {
                $backendTable = $attribute->getBackendTable();
                if ($backendTable) {
                    $describe = Mage::getSingleton('core/resource')->getConnection('core_write')->describeTable($backendTable);

                    $prop = $describe[$attributeCode];

                    $indexDataTypes = array(
                        'varchar',
                        'varbinary',
                        'char',
                        'date',
                        'datetime',
                        'timestamp',
                        'time',
                        'year',
                        'enum',
                        'set',
                        'bit',
                        'bool',
                        'tinyint',
                        'smallint',
                        'mediumint',
                        'int',
                        'bigint',
                        'float',
                        'double',
                        'decimal',
                    );

                    if (in_array($prop['DATA_TYPE'], $indexDataTypes)) {
                        return $prop['DATA_TYPE'];
                    }
                }
            }

            return $attributeDataType;
        }

        // Check if attribute is customer address attribute
        if (!$isCustomerAttribute)
        {
            // Get customer address attributes
            $customerAddressAttributesArray = array();
            $customerAddressAttributes = Mage::getModel('customer/address')->getAttributes();
            foreach ($customerAddressAttributes as $customerAddressAttribute) {
                if (($label = $customerAddressAttribute->getName()))
                {
                    $customerAddressAttributesArray[$label] = $label;
                }
            }

            if (in_array($attributeCode, $customerAddressAttributesArray))
            {
                // If customer address attribute, then load attribute
                $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('customer_address', $attributeCode);
                $attributeDataType = $attribute->getData('backend_type');

                // If the attribute data type is static, then we have to load the table and check the DATA_TYPE column
                // to see what is the data type for that attribute
                if ($attributeDataType == 'static')
                {
                    $backendTable = $attribute->getBackendTable();
                    if ($backendTable) {
                        $describe = Mage::getSingleton('core/resource')->getConnection('core_write')->describeTable($backendTable);

                        $prop = $describe[$attributeCode];

                        $indexDataTypes = array(
                            'varchar',
                            'varbinary',
                            'char',
                            'date',
                            'datetime',
                            'timestamp',
                            'time',
                            'year',
                            'enum',
                            'set',
                            'bit',
                            'bool',
                            'tinyint',
                            'smallint',
                            'mediumint',
                            'int',
                            'bigint',
                            'float',
                            'double',
                            'decimal',
                        );

                        if (in_array($prop['DATA_TYPE'], $indexDataTypes)) {
                            return $prop['DATA_TYPE'];
                        }
                    }
                }

                return $attributeDataType;
            }
        }

        return '';
    }

    /**
     * Get data type from Doppler field
     *
     * Possible results:
     * boolean
     * number
     * string (400 character max)
     * date (yyyy-MM-dd)
     * gender (M or F)
     * country (ISO 3166-1 alpha-2)
     *
     * @param string $dopplerFieldName
     * @return string
     */
    public function getDopplerFieldDataType($dopplerFieldName)
    {
        $this->_fieldsArray = array();

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '') {
            // Get cURL resource
            $ch = curl_init();

            // Set url
            curl_setopt($ch, CURLOPT_URL, 'https://restapi.fromdoppler.com/accounts/' . $usernameValue. '/fields');

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

            if($resp)
            {
                $responseContent = json_decode($resp, true);
                $fieldsResponseArray = $responseContent['items'];

                foreach ($fieldsResponseArray as $field)
                {
                    $fieldName = $field['name'];

                    if ($fieldName == $dopplerFieldName)
                    {
                        return $field['type'];
                    }
                }
            }

            // Close request to clear up some resources
            curl_close($ch);
        }

        return '';
    }

    /**
     * Get date in format yyyy-MM-dd
     *
     * Native Magento format: yyyy-mm-dd hh:mm:ss
     *
     * @return string
     */
    public function getFormattedDate($date)
    {
        $formattedDate = '';

        if ($date)
        {
            $dateTime = strtotime($date);
            $formattedDate = date('Y-m-d', $dateTime);
        }

        return $formattedDate;
    }
}