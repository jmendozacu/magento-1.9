<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Mysql4_Buy_Template_Synchronization
    extends Ess_M2ePro_Model_Mysql4_Component_Child_Abstract
{
    // ########################################

    protected $_isPkAutoIncrement = false;

    // ########################################

    public function _construct()
    {
        $this->_init('M2ePro/Buy_Template_Synchronization', 'template_synchronization_id');
        $this->_isPkAutoIncrement = false;
    }

    // ########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return;
        }

        $settings = array(
            'listing' => 'revise_change_listing',
            'sellingFormatTemplate' => 'revise_change_selling_format_template'
        );

        $settings = $this->getEnabledReviseSettings($newData, $oldData, $settings);

        if (!$settings) {
            return;
        }

        $idsByReasonDictionary = array();
        foreach ($listingsProducts as $listingProduct) {

            if ($listingProduct['synch_status'] != Ess_M2ePro_Model_Listing_Product::SYNCH_STATUS_SKIP) {
                continue;
            }

            $listingProductSynchReasons = array_unique(array_filter(explode(',',$listingProduct['synch_reasons'])));
            foreach ($listingProductSynchReasons as $reason) {
                $idsByReasonDictionary[$reason][] = $listingProduct['id'];
            }
        }

        $idsForUpdate = array();
        foreach ($settings as $reason => $setting) {

            if (!isset($idsByReasonDictionary[$reason])) {
                continue;
            }

            $idsForUpdate = array_merge($idsForUpdate, $idsByReasonDictionary[$reason]);
        }

        $this->_getWriteAdapter()->update(
            Mage::getResourceModel('M2ePro/Listing_Product')->getMainTable(),
            array('synch_status' => Ess_M2ePro_Model_Listing_Product::SYNCH_STATUS_NEED),
            array('id IN (?)' => array_unique($idsForUpdate))
        );
    }

    // ----------------------------------------

    public function getEnabledReviseSettings($newData, $oldData, $settings)
    {
        foreach ($settings as $reason => $setting) {

            if (!isset($newData[$setting], $oldData[$setting])) {
                unset($settings[$reason]);
                continue;
            }

            // we need change from 0 to 1 only
            if ($oldData[$setting] || !$newData[$setting]) {
                unset($settings[$reason]);
                continue;
            }
        }

        return $settings;
    }

    // ########################################
}