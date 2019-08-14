<?php
/**
 * SureTax Customer Group Configuration controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_CustomergroupController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/customergroup');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('suretax/suretax_customergroup');
    }

    public function editAction()
    {
        Utility::logMessage('edit ', Zend_Log::NOTICE);
        $this->_title($this->__('SureTax'))
            ->_title($this->__('Customer'))
            ->_title($this->__('Manage Customer'));

        $grpId  = $this->getRequest()->getParam('id');
        $suretaxCustomerGroupId  = $this->getRequest()->getParam('suretaxId');
        $salesTypeCode = Constants::DEFAULT_SALES_TYPE_CODE;
        $exemptionCode = Constants::DEFAULT_EXEMPTION_CODE;
        $exemptionReason = Constants::DEFAULT_EXEMPTION_REASON_CODE;

        $customerGroup = Mage::getModel('customer/group')->load($grpId);
        $groupName = $customerGroup->getData('customer_group_code');

        if ($suretaxCustomerGroupId !== null) {
            $suretaxCustomerGrp = Mage::getModel(Constants::CUST_GRP_TBL)
                ->load($suretaxCustomerGroupId);
            $salesTypeCode = $suretaxCustomerGrp->getSalesTypeCode();
            $exemptionCode = $suretaxCustomerGrp->getExemptionCode();
            $exemptionReason = $suretaxCustomerGrp->getExemptionReasonCode();
        }

        $config = array(
            'groupId'=>$grpId,
            'suretaxGrpId' => $suretaxCustomerGroupId,
            'grpName' => $groupName,
            'sales_type_code' => $salesTypeCode,
            'exemption_code' => $exemptionCode,
            'exemption_reason' => $exemptionReason
        );

        Mage::register('custGrpConfig', $config);
        $this->loadLayout()->_setActiveMenu('suretax/customergroup');

        return $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            $suretaxGroupId = $this->getRequest()->getParam('suretaxGrpId');
            $customerGroupId = $this->getRequest()->getParam('customerGroupId');
         
            if ($suretaxGroupId !== null) {
                $suretaxCustomerGroup = Mage::getModel(Constants::CUST_GRP_TBL)
                    ->load((int)$suretaxGroupId);
            } else {
                $suretaxCustomerGroup = Mage::getModel(Constants::CUST_GRP_TBL);
            }
            
            $salesTypeCode = Constants::DEFAULT_SALES_TYPE_CODE;
            $exemptionCode = Constants::DEFAULT_EXEMPTION_CODE;
            $exemptionReason = Constants::DEFAULT_EXEMPTION_REASON_CODE;

            if (!($this->getRequest()->getParam('sales_type_code') === null)) {
                $salesTypeCode = $this->getRequest()->getParam('sales_type_code');
            }
            if (!($this->getRequest()->getParam('exemption_code') === null)) {
                $exemptionCode = $this->getRequest()->getParam('exemption_code');
            }
            if ($this->getRequest()->getParam('exemption_reason')) {
                $exemptionReason = $this->getRequest()->getParam('exemption_reason');
            }
            Utility::logMessage($salesTypeCode, Zend_Log::NOTICE);
            Utility::logMessage($exemptionCode, Zend_Log::NOTICE);
            Utility::logMessage($exemptionReason, Zend_Log::NOTICE);

            $suretaxCustomerGroup->setSalesTypeCode($salesTypeCode);
            $suretaxCustomerGroup->setExemptionCode($exemptionCode);
            $suretaxCustomerGroup->setExemptionReasonCode($exemptionReason);

            if (!($customerGroupId === null)) {
                $suretaxCustomerGroup->setSuretaxCustomerGroupId($customerGroupId);
            }
            $suretaxCustomerGroup->save();
            Mage::getSingleton('adminhtml/session')
                ->addSuccess($this->__('Your SureTax Customer Group configuration has been saved!'));
        } catch (Exception $t) {
            Utility::logMessage(
                'Exception when saving customer group configuration: ' .
                $t->getMessage(), Zend_Log::ERR
            );
            Mage::getSingleton('adminhtml/session')->addErrors(
                $this->__(
                    'Your SureTax Customer Group configuration did not save successfully. ' .
                    'Please check the logs.'
                )
            );
        }
        
        $this->_redirect('*/*/index');
    }

    public function deleteAction()
    {
        try {
            if (!($this->getRequest()->getParam('suretaxId') === null)) {
                $suretaxGroup = Mage::getModel(Constants::CUST_GRP_TBL);
                $suretaxGroup->setId($this->getRequest()->getParam('suretaxId'));
                $suretaxGroup->delete();
            }
            Mage::getSingleton('adminhtml/session')
                ->addSuccess('Your SureTax Customer Group configuration was successfully deleted.');
        } catch (Exception $e) {
            Utility::logMessage(
                'Exception when deleting customer group configuration: ' .
                $e->getMessage(), Zend_Log::ERR
            );
            Mage::getSingleton('adminhtml/session')->addErrors(
                $this->__(
                    'Your SureTax Customer Group configuration did not delete successfully. ' .
                    'Please check the logs.'
                )
            );
        }
        
        $this->_redirect('*/*/index');
    }

    public function selectAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/group');
        return $this->renderLayout();
    }
}
