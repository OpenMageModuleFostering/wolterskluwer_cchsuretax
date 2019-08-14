<?php
/*
 * SureTax Customer Group form edit container.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container_Edit 
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'suretaxId';
        parent::__construct();      
        
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_customergroup_container';
        $this->_updateButton('save', 'label', Mage::helper('suretax')->__('Save Group Configuration'));
        $this->_updateButton('back', 'label', Mage::helper('suretax')->__('Cancel'));      
                   
    }
    
    public function getHeaderText()
    {
        $customerConfig = Mage::registry('custGrpConfig');
        $nm = $customerConfig['grpName'];
        $suretaxGrpId = $customerConfig['suretaxGrpId'];
        
        if (!($suretaxGrpId === null)) {
            $str = Mage::helper('suretax')->__('Edit SureTax Configuration For Customer Group ' . $nm);
        } else {
            $str = Mage::helper('suretax')->__('Create SureTax Configuration For Customer Group ' . $nm);
        }
        
        return $str;
    }
    
    public function getFormActionUrl()
    {
        return $this->getUrl('*/suretax_customergroup/save/');
    }
    
    public function getDeleteUrl()
    {
        $groupConfig = Mage::registry('custGrpConfig');
        return $this->getUrl('*/suretax_customergroup/delete/', array('suretaxId'=>$groupConfig['suretaxGrpId']));
    }
}
