<?php
/*
 * SureTax Customer form edit container.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customer_Container_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'suretaxId';
        parent::__construct();
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_customer_container';
        $this->_updateButton('save', 'label', Mage::helper('suretax')->__('Save Customer Configuration'));
        $this->_updateButton('back', 'label', Mage::helper('suretax')->__('Cancel'));
    }
    
    public function getHeaderText()
    {
        $customerConfig = Mage::registry('customerConfig');
        $nm = $customerConfig['name'];
        $suretaxCustomerId = $customerConfig['suretaxCustId'];
        
        if (!($suretaxCustomerId === null)) {
            return Mage::helper('suretax')->__('Edit SureTax Configuration For Customer ' . $nm);
        } else {
            return Mage::helper('suretax')->__('Create SureTax Configuration For Customer ' . $nm);
        }
    }
    
    public function getFormActionUrl()
    {
        return $this->getUrl('*/suretax_customer/save/');
    }
    
    public function getDeleteUrl()
    {
        $customerConfig = Mage::registry('customerConfig');
        return $this->getUrl('*/suretax_customer/delete/', array('suretaxId'=>$customerConfig['suretaxCustId']));
    }
}
