<?php
/*
 * SureTax Advanced section in the Global configuration.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_SuretaxConfig_Edit_Tab_Advanced extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * This is the Advanced section of SureTax General
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset(
            'base_fieldset', array(
            'legend'=>Mage::helper('suretax')->__('Advanced Information'),
            'class'=>'fieldset-wide'
            )
        );

        $fieldset->addField(
            'finalized_location', 'select', array(
            'name' => 'finalized_location',
            'label' => Mage::helper('suretax')->__('Finalize Transaction at '),
            'title' => Mage::helper('suretax')->__('Finalize Transaction at '),
            'values' => array('0'=>'Place Order','1' => 'Order is set to Complete'),
            'required' => true
            )
        );

        $fieldset->addField(
            'tax_situs', 'select', array(
            'name' => 'tax_situs',
            'label' => Mage::helper('suretax')->__('Tax Calculation Based On '),
            'title' => Mage::helper('suretax')->__('Tax Calculation Based On '),
            'values' => array('0'=>'Shipping Address','1' => 'Billing  Address', '2' => 'Shipping Origin'),
            'required' => false
            )
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
