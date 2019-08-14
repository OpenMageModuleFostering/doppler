<?php
/**
 * MakingSense_Doppler module observer
 *
 * @category    MakingSense
 * @package     Doppler
 
 */
class MakingSense_Doppler_Model_Observer
{
    /**
     * When an user registers, then send customer data to Doppler default list
    */
    public function userRegistration()
    {
        // If the customer is logged in after the observer dispatch, that means that the customer was successfully registered
        if (Mage::getSingleton('customer/session')->isLoggedIn())
        {
            // Get customer
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            // Get default Doppler list
            $defaultDopplerList = Mage::helper('makingsense_doppler')->getDefaultDopplerList();

            if ($defaultDopplerList)
            {
                // Export customer to Doppler
                $dopplerMappedFields = Mage::helper('makingsense_doppler')->getDopplerMappedFields();
                $mappedFieldsCount = count($dopplerMappedFields);
                $leadMappingArrayKeys = array_keys($dopplerMappedFields);
                $dopplerAttributeTypes = array();
                for ($i = 0; $i < $mappedFieldsCount; $i++) {
                    $fieldName = $leadMappingArrayKeys[$i];
                    $dopplerAttributeTypes[$fieldName] = Mage::helper('makingsense_doppler')->getDopplerFieldDataType($fieldName);
                }
                $dopplerMappedFields = Mage::helper('makingsense_doppler')->getDopplerMappedFields();
                $exportError = Mage::helper('makingsense_doppler')->exportCustomerToDoppler($customer, $defaultDopplerList, $dopplerMappedFields, $dopplerAttributeTypes);
            }
        }
    }

    /**
     * When an user creates a new order and register, then send customer data to Doppler default list
     * @param $observer
    */
    public function checkoutUserRegistration($observer)
    {
        /** @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();

        // Validate that the checkout method was "register"
        if ($quote->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            return;
        }

        // Get customer
        $customer = $quote->getCustomer();

        // Get default Doppler list
        $defaultDopplerList = Mage::helper('makingsense_doppler')->getDefaultDopplerList();

        if ($defaultDopplerList)
        {
            // Export customer to Doppler
            $dopplerMappedFields = Mage::helper('makingsense_doppler')->getDopplerMappedFields();
            $mappedFieldsCount = count($dopplerMappedFields);
            $leadMappingArrayKeys = array_keys($dopplerMappedFields);
            $dopplerAttributeTypes = array();
            for ($i = 0; $i < $mappedFieldsCount; $i++) {
                $fieldName = $leadMappingArrayKeys[$i];
                $dopplerAttributeTypes[$fieldName] = Mage::helper('makingsense_doppler')->getDopplerFieldDataType($fieldName);
            }
            $exportError = Mage::helper('makingsense_doppler')->exportCustomerToDoppler($customer, $defaultDopplerList, $dopplerMappedFields, $dopplerAttributeTypes);
        }
    }
}