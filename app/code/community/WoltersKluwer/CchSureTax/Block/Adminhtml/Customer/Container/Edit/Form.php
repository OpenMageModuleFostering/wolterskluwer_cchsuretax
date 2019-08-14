<?php
/*
 * SureTax Customer Edit Form.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customer_Container_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
            'id'=>'edit_form',
            'action'=>$this->getData('action'),
            'method'=>'post'
            )
        );
        
        $customerConfig = Mage::registry('customerConfig');
        $customerId = $customerConfig['customerId'];
        $customerGroupId = $customerConfig['customerGroupId'];
        
        $suretaxCustomer = Mage::getModel(Constants::CUST_TBL)
            ->load($customerId, 'suretax_customer_id');
        
        $salesTypeCode = $customerConfig['sales_type_code'];
        $exemptionCode = $customerConfig['exemption_code'];
        $exemptionReason = $customerConfig['exemption_reason'];

        if (!($customerGroupId === null)) {
            $custId = $suretaxCustomer->getId();
            
            if (empty($custId)) {
                WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                    'inside with customer group id ' . $customerGroupId
                    . 'use customer group settings',
                    Zend_Log::DEBUG
                );
                
                $sureTaxCustomerGroup = Mage::getModel(Constants::CUST_GRP_TBL)
                    ->load($customerGroupId, 'suretax_customer_group_id');
                $sureTaxCustomerGroupId = $sureTaxCustomerGroup->getId();
                
                if (!empty($sureTaxCustomerGroupId)) {
                    $salesTypeCode = $sureTaxCustomerGroup->getSalesTypeCode();
                    $exemptionCode = $sureTaxCustomerGroup->getExemptionCode();
                    $exemptionReason = $sureTaxCustomerGroup->getExemptionReasonCode();
                }
            }
        }
        
        $fieldset = $form->addFieldset(
            'suretax_fieldset', array(
            'legend' => Mage::helper('customer')->__('SureTax Information')
            )
        );
        
        $fieldset->addField(
            'sales_type_code', 'select', array(
            'name' => 'sales_type_code',
            'label' => Mage::helper('customer')->__('Sales Type Code'),
            'title' => Mage::helper('customer')->__('Sales Type Code'),
            'class' => 'required-entry',
            'required' => true,
            'value' => isset($salesTypeCode) ? $salesTypeCode : $this->__('Residential customer'),
            'values' => Constants::$SALES_TYPE_CODES
            )
        );
               
        $disable = false;
        if (isset($exemptionCode)) {
            if ($exemptionCode == 'None') {
                $disable = true;
            }
        } else {
            $disable = true;
        }
        
        $exemptionCodeSet = $fieldset->addField(
            'exemption_code', 'select', array(
            'name' => 'exemption_code',
            'label' => Mage::helper('customer')->__('Exemption Code'),
            'title' => Mage::helper('customer')->__('Exemption Code'),
            'class' => 'required-entry',
            'onchange'=>'getExemptionReason(this)',
            'required' => true,
            'value' => $exemptionCode,
            'values' => Constants::$EXEMPTION_TYPE_CODES
            )
        );
        
        $fieldset->addField(
            'exemption_reason', 'select', array(
            'name' => 'exemption_reason',
            'label' => Mage::helper('customer')->__('Exemption Reason'),
            'title' => Mage::helper('customer')->__('Exemption Reason'),
            'class' => 'required-entry',
            'required' => true,
            'disabled' => $disable,
            'value' => $exemptionReason,
            'values' => Constants::$EXEMPTION_REASON_CODES
            )
        );       
 
        $exemptionCodeSet->setAfterElementHtml(
            "<script type = \"text/javascript\">
              
            function getExemptionReason(selectElement) {
                if(selectElement.value == 'None') {
                    $('exemption_reason').value = 'None';
                    $('exemption_reason').disabled = true;
                }else {
                    $('exemption_reason').disabled = false;
                }
            }
        </script>"
        );
        
        if (!($suretaxCustomer->getId() === null)) {
            $form->addField(
                'suretaxCustomerId', 'hidden', array(
                'name' => 'suretaxCustomerId',
                'value' => $suretaxCustomer->getId(),
                )
            );
        }
        
        if (!($customerId === null)) {
            $form->addField(
                'customerId', 'hidden', array(
                'name' => 'id',
                'value' => $customerId,
                )
            );
        }
        
        if (!($customerGroupId === null)) {
            $form->addField(
                'customerGroupId', 'hidden', array(
                'name' => 'customerGroupId',
                'value' => $customerGroupId,
                )
            );
        }
        
        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
