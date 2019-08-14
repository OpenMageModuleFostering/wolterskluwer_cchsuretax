<?php
/*
 * SureTax Customer Group Edit Form.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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

        $customerConfig = Mage::registry('custGrpConfig');
        
        $groupId = $customerConfig['groupId'];
        $suretaxGroupId = $customerConfig['suretaxGrpId'];

        $salesTypeCode = $customerConfig['sales_type_code'];
        $exemptionCode = $customerConfig['exemption_code'];
        $exemptionReason = $customerConfig['exemption_reason'];

        $fieldset = $form->addFieldset(
            'suretax_fieldset', array(
            'legend' => Mage::helper('customer')->__('SureTax Information')
            )
        );

        $fieldset->addField(
            'sales_type_code', 'select', array(
            'name' => 'sales_type_code',
            'label' => Mage::helper('suretax')->__('Sales Type Code'),
            'title' => Mage::helper('suretax')->__('Sales Type Code'),
            'class' => 'required-entry',
            'required' => true,
            'value' => isset($salesTypeCode) ? $salesTypeCode : $this->__('Residential customer'),
            'values' => WoltersKluwer_CchSureTax_Helper_Constants::$SALES_TYPE_CODES
            )
        );

        $exemptionCodeSet = $fieldset->addField(
            'exemption_code', 'select', array(
            'name' => 'exemption_code',
            'label' => Mage::helper('suretax')->__('Exemption Code'),
            'title' => Mage::helper('suretax')->__('Exemption Code'),
            'class' => 'required-entry',
            'onchange'=>'getExemptionReason(this)',
            'required' => true,
            'value' => $exemptionCode,
            'values' => WoltersKluwer_CchSureTax_Helper_Constants::$EXEMPTION_TYPE_CODES
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

        $exemptionReasonCodeSet = $fieldset->addField(
            'exemption_reason', 'select', array(
            'name' => 'exemption_reason',
            'label' => Mage::helper('suretax')->__('Exemption Reason'),
            'title' => Mage::helper('suretax')->__('Exemption Reason'),
            'class' => 'required-entry',
            'required' => true,
            'disabled' => $disable,
            'value' => $exemptionReason,
            'values' => WoltersKluwer_CchSureTax_Helper_Constants::$EXEMPTION_REASON_CODES
            )
        );

        $exemptionReasonCodeSet->setAfterElementHtml(
            "<script type=\"text/javascript\">

                    if(($('exemption_code').value == 'None') )){
                       $('exemption_reason').disabled = true;
                   } else {
                       $('exemption_reason').disabled = false;
                   }
        </script>"
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

        if (!($groupId === null)) {
            $form->addField(
                'customerGroupId', 'hidden', array(
                'name' => 'customerGroupId',
                'value' => $groupId,
                )
            );
        }

        if (!($suretaxGroupId === null)) {
            $form->addField(
                'suretaxGroupId', 'hidden', array(
                'name' =>'suretaxGrpId', 'value'=>$suretaxGroupId
                )
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
