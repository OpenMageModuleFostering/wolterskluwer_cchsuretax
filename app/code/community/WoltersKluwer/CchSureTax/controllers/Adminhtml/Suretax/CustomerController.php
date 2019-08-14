<?php
/**
 * SureTax Customer Configuration controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_CustomerController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/customer');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_customer');
    }

    public function editAction()
    {
        $this->_title($this->__('SureTax'))
            ->_title($this->__('Customer'))
            ->_title($this->__('Manage Customer'));

        $customerId  = $this->getRequest()->getParam('id');
        $suretaxcustomerId  = $this->getRequest()->getParam('suretaxId');
        
        // pull up customer exemption info
        $customerExemptionArray = WoltersKluwer_CchSureTax_Helper_Data::
            getCustomerExemptionInfo($customerId, $suretaxcustomerId);
        $salesTypeCode = $customerExemptionArray['salesTypeCode'];
        $exemptionCode = $customerExemptionArray['exemptionCode'];
        $exemptionReason = $customerExemptionArray['exemptionReason'];
        $name = $customerExemptionArray['name'];
        $email = $customerExemptionArray['email'];
        $group = $customerExemptionArray['group'];
        $groupName = $customerExemptionArray['groupName'];

        WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
            'id='.$customerId.' name= '.
            $name. ' group= '.$group. 'sales type code = '.$salesTypeCode.' exemption code '.
            $exemptionCode. ' exemption reason'. $exemptionReason, Zend_Log::ERR
        );

        WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
            'id='.$customerId.' name= '.
            $name. ' group= '.$group. 'sales type code = '.$salesTypeCode.' exemption code '.
            $exemptionCode. ' exemption reason'. $exemptionReason, Zend_Log::DEBUG
        );

        $config = array(
            'customerId'=>$customerId,
            'name'=>$name,
            'sales_type_code'=>$salesTypeCode,
            'exemption_code'=>$exemptionCode,
            'exemption_reason'=>$exemptionReason,
            'email'=>$email,
            'groupName'=>$groupName,
            'suretaxCustId'=>$suretaxcustomerId,
            'customerGroupId'=>$group,
        );
        Mage::register('customerConfig', $config);

        $this->loadLayout()
            ->_setActiveMenu('suretax/customer');

        return $this->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('suretax/customer');
        return $this;
    }

    /**
     * Saving the Website or Store configuration.
     */
    public function saveAction()
    {

        $customerId = $this->getRequest()->getParam('id');

        $suretaxCustomerId = $this->getRequest()->getParam('suretaxCustomerId');
        $salesTypeCode = $this->getRequest()->getParam('sales_type_code');
        $exemptionCode = $this->getRequest()->getParam('exemption_code');
        $exemptionReason = $this->getRequest()->getParam('exemption_reason');

        if ($exemptionCode !== null && $exemptionReason === null) {
            $exemptionReason = Constants::DEFAULT_EXEMPTION_REASON_CODE;
        }
        try {
            if ($customerId !== null) {
                if ($suretaxCustomerId !== null) {
                    $suretaxCustomer = Mage::getModel(Constants::CUST_TBL)
                        ->load($customerId, 'suretax_customer_id');
                } else {
                    $suretaxCustomer = Mage::getModel(Constants::CUST_TBL);
                    $suretaxCustomer->setSuretaxCustomerId($customerId);
                }
                $suretaxCustomer->setSalesTypeCode($salesTypeCode);
                $suretaxCustomer->setExemptionCode($exemptionCode);
                $suretaxCustomer->setExemptionReasonCode($exemptionReason);
                $suretaxCustomer->save();

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess($this->__('Your SureTax Customer configuration has been saved!'));
            }
        } catch (Exception $e) {
            WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                'Exception when saving customer configuration: ' .
                $e->getMessage(), Zend_Log::ERR
            );
            Mage::getSingleton('adminhtml/session')->addErrors(
                $this->__(
                    'Your SureTax Customer configuration did not save successfully. ' .
                    'Please check the logs.'
                )
            );
        }
            $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        try {
            if (!($this->getRequest()->getParam('suretaxId') === null)) {
                $suretaxCustomer = Mage::getModel(Constants::CUST_TBL);
                $suretaxCustomer->setId($this->getRequest()->getParam('suretaxId'));
                $suretaxCustomer->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess($this->__('Your SureTax Customer configuration was successfully deleted.'));
            }
        } catch (Exception $e) {
            WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                'Exception when deleting customer configuration: ' .
                $e->getMessage(), Zend_Log::ERR
            );
            Mage::getSingleton('adminthml/session')->addErrors(
                $this->__(
                    'Your SureTax Customer configuration did not delete successfully. ' .
                    'Please check the logs.'
                )
            );
        }
        $this->_redirect('*/*/');
    }

    public function selectCustomerAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/customer');
        return $this->renderLayout();
    }
}
