<?php
/*
 * SureTax Global configuration Edit form container.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_SuretaxConfig_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'info_id';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_suretaxconfig';
        parent::__construct();
        
        $this->_removeButton('reset');
        $this->_removeButton('back');
    }
    
    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('SureTax Global Configuration');
    }
    
    public function getFormActionUrl()
    {
        return $this->getUrl('*/suretax_config/save');
    }
}
