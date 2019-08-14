<?php
/**
 * SureTax Website/Store Configuration controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_WebsitestoreController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/websitestore');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_websitestore');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('suretax/websitestore');
        return $this;
    }

    /**
     * Edit action
     */
    public function editAction()
    {
        $this->_title($this->__('SureTax'))
            ->_title($this->__('Website/Store'))
            ->_title($this->__('Manage Website and Store'));

        $websiteId  = $this->getRequest()->getParam('websiteId');
        $storeId  = $this->getRequest()->getParam('storeId');
        $name = $this->getRequest()->getParam('name');
        $config = array ('storeId' => $storeId,
                         'websiteId' => $websiteId,
                         'name' => $name);

        Mage::register('config', $config);

        $this->loadLayout()
            ->_setActiveMenu('suretax/websitestore');

        return $this->renderLayout();
    }

    /**
     * saving the Website or Store configuration.
     */
    public function saveAction()
    {
        WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
            'Saving website or store',
            Zend_Log::NOTICE
        );

        try {
            $request = $this->getRequest();
            $formData = $request->getPost();

            $websiteId = $formData['website_id'];
            $storeId = $formData['store_id'];
            $isEnableCalc = $formData['is_enable_calc'];
            $useBusinessUnit = $formData['use_business_unit'];
            $businessUnit = isset($formData['business_unit']) ?
                $formData['business_unit'] : '';
            $useDefaultAddress = $formData['use_default_address'];
            $streetAddress1 = isset($formData['ship_from_address1']) ?
                $formData['ship_from_address1'] : '';
            $streetAddress2 = isset($formData['ship_from_address2']) ?
                $formData['ship_from_address2'] : '';
            $city = isset($formData['ship_from_city']) ?
                $formData['ship_from_city'] : '';
            $country = isset($formData['ship_from_country']) ?
                $formData['ship_from_country'] : '';
            $stateProvince = isset($formData['ship_from_state']) ?
                $formData['ship_from_state'] : '';
            $zipPostal = isset($formData['ship_from_zip']) ?
                $formData['ship_from_zip'] : '';

            if ($storeId == 0) {
                $row = Mage::getModel(Constants::WS_CONFIG_TBL)
                    ->getCollection()
                    ->loadWebsiteConfig($websiteId);
            } else {
                $row = Mage::getModel(Constants::WS_CONFIG_TBL)
                    ->getCollection()
                    ->loadStoreConfig($websiteId, $storeId);
            }

            $row->setData(
                array(
                    'id' => $row->getId(),
                    'website_id'=>$websiteId,
                    'store_id'=>$storeId,
                    'is_enable_calc'=>$isEnableCalc,
                    'use_business_unit'=>$useBusinessUnit,
                    'business_unit'=>$businessUnit,
                    'use_default_address'=>$useDefaultAddress,
                    'street_address1'=>$streetAddress1,
                    'street_address2'=>$streetAddress2,
                    'city'=>$city,
                    'country'=>$country,
                    'state_province'=>$stateProvince,
                    'zip_postal'=>$zipPostal
                )
            )->save();

            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__("Your SureTax Website/Store configuration has been saved.")
            );
        } catch (Exception $ex) {
            WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                'Exception when saving Website/Store configuration: ' .
                $ex->getMessage(), Zend_Log::ERR
            );
            Mage::getSingleton('adminhtml/session')->addErrors(
                $this->__(
                    'Your SureTax Website/Store configuration did not save! ' .
                    'Please check the logs.'
                )
            );
        }
        $this->_redirect('*/*/index');
    }
}
