<?php
class MakingSense_Doppler_Block_Adminhtml_Subscribers_Grid_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $dopplerExportStatus = $row->getData($this->getColumn()->getIndex());

        // If the status is not "Pending (null)" or "Processing", then it's an error code for that customer
        if (!$dopplerExportStatus)
        {
            $dopplerExportStatus = $this->__('Pending');
        } else  {

            if ($dopplerExportStatus == 'Processing')
            {
                $dopplerExportStatus = $this->__('Processing');
            } else {
                $dopplerExportStatus =$this->__($dopplerExportStatus);
            }
        }

        return $dopplerExportStatus;
    }
}