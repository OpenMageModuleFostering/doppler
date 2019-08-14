<?php
class MakingSense_Doppler_Block_Adminhtml_Page_Menu extends Mage_Adminhtml_Block_Page_Menu {

    /**
     * Initialize template and cache settings
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $usernameValue = Mage::getStoreConfig('doppler/connection/username');
        $apiKeyValue = Mage::getStoreConfig('doppler/connection/key');

        if($usernameValue != '' && $apiKeyValue != '') {
            if($usernameValue != '' && $apiKeyValue != '')
            {
                $status = Mage::helper('makingsense_doppler')->testAPIConnection();
                $this->setActiveDopplerApi($status);
            }
        } else {
            $this->setActiveDopplerApi(false);
        }
        
        $defaultListEnabled = Mage::helper('makingsense_doppler')->isDefaultListEnabled();
        $this->setDefaultListEnabled($defaultListEnabled);
    }

}