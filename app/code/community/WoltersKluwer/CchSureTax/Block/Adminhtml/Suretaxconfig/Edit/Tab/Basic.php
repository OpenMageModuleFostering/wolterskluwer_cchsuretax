<?php
/*
 * SureTax Basic Section in the Global configuration.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_SuretaxConfig_Edit_Tab_Basic extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * This is the Basic section of SureTax General Configuration
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $suretaxConfigData = WoltersKluwer_CchSureTax_Helper_Utility::getSureTaxConfigData();

        $allProductTaxClass = Mage::getModel('tax/class_source_product')->toOptionArray();

        $fieldset = $form->addFieldset(
            'base_fieldset', array(
            'legend'=>Mage::helper('suretax')->__('Basic Information'),
            'class'=>'fieldset'
            )
        );

        $fieldset->addField(
            'enable_suretax', 'select', array(
            'label'     => Mage::helper('suretax')->__('Enable SureTax Calculation'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'is_suretax_enabled',
            'onclick' => "",
            'onchange' => "",
            'value'  => isset($suretaxConfigData['is_suretax_enabled']) ?
                $suretaxConfigData['is_suretax_enabled'] : '0',
            'values' => array('0'=>'No','1' => 'Yes'),
            'disabled' => false,
            'readonly' => false,
            'after_element_html' => '<td class="scope-label">[GLOBAL]</td>',
            'tabindex' => 0
            )
        );

        $fieldset->addField(
            'clientnumber', 'text', array(
            'name' => 'clientnumber',
            'label' => Mage::helper('suretax')->__('Client Number'),
            'title' => Mage::helper('suretax')->__('Client Number'),
            'required' => true,
            'value' => isset($suretaxConfigData['clientnumber']) ?
                $suretaxConfigData['clientnumber'] : '',
            'note' => $this->__('<small>Enter Client Number Provided by SureTax</small>')
            )
        );

        $fieldset->addField(
            'suretaxurl', 'text', array(
            'name' => 'suretaxurl',
            'label' => Mage::helper('suretax')->__('SureTax Webservice URL'),
            'title' => Mage::helper('suretax')->__('SureTax Webservice URL'),
            'required' => true,
            'value' => isset($suretaxConfigData['suretaxurl']) ?
                $suretaxConfigData['suretaxurl'] : '',
            'note' => $this->__('<small>Enter SureTax URL to connect to API</small>')
            )
        );

        $fieldset->addField(
            'validationkey', 'password', array(
            'name' => 'validationkey',
            'label' => Mage::helper('suretax')->__('Validation Key'),
            'title' => Mage::helper('suretax')->__('Validation Key'),
            'required' => true,
            'value' => isset($suretaxConfigData['validationkey']) ?
                $suretaxConfigData['validationkey'] : '',
            'note' => $this->__('<small>Enter Company Validation Key Provided by SureTax</small>')
            )
        );

        $fieldset->addField(
            'defaultbusinessunit', 'text', array(
            'name' => 'defaultbusinessunit',
            'label' => Mage::helper('suretax')->__('Global Business Unit'),
            'title' => Mage::helper('suretax')->__('Global Business Unit'),
            'required' => false,
            'maxlength' => WoltersKluwer_CchSureTax_Helper_Constants::BUSINESS_UNIT_MAX_LENGTH,
            'value' => isset($suretaxConfigData['defaultbusinessunit']) ?
                $suretaxConfigData['defaultbusinessunit'] : '',
            'note' => $this->__('<small>Enter the Business Unit for transactions</small>')
            )
        );

        $fieldset->addField(
            'select_provider_type', 'select', array(
            'label'     => Mage::helper('suretax')->__('Provider Type'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'providertype',
            'onclick' => "",
            'onchange' => "",
            'value' => isset($suretaxConfigData['providertype']) ?
                $suretaxConfigData['providertype'] : '70',
            'values' => array('70' => '70 - Retail', '99' => '99 - Default'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
            )
        );

        $fieldset->addField(
            'shipping_tax_class', 'select', array(
            'label'     => Mage::helper('suretax')->__('Tax Class for Shipping'),
            'class'     => 'required-entry',
            'required'  => false,
            'name'      => 'shipping_tax_class',
            'values' => $allProductTaxClass,
            'value' => isset($suretaxConfigData['shipping_tax_class']) ?
                $suretaxConfigData['shipping_tax_class'] : '1',
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
            )
        );

        $fieldset->addField(
            'gift_wrap_tax_class', 'select', array(
            'label'     => Mage::helper('suretax')->__('Tax Class for Gift Options'),
            'class'     => 'required-entry',
            'required'  => false,
            'name'      => 'gift_wrap_tax_class',
            'values' => $allProductTaxClass,
            'value' => isset($suretaxConfigData['gift_wrap_tax_class']) ?
                $suretaxConfigData['gift_wrap_tax_class'] : '1',
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
            )
        );

        $fieldset->addField(
            'logging', 'select', array(
            'label'     => Mage::helper('suretax')->__('Enable Debug Logging'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'is_logging_enabled',
            'value' => isset($suretaxConfigData['is_logging_enabled']) ?
                $suretaxConfigData['is_logging_enabled'] : '0',
            'values' => array('0'=>'No','1' => 'Yes'),
            'disabled' => false,
            'readonly' => false,
            'note' => $this->__(
                '<small>DEBUG and INFO level logging including API '
                . 'requests and responses will be logged.</small>'
            ),
            'tabindex' => 0
            )
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
